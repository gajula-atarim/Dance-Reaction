<?php
/**
 * About page for Dance Reaction (run through Atarim's execute-php with DR_REF and DR_STEP defined).
 *
 * DR_STEP = 'media' — imports the About photos into the Media Library (skips ones already imported;
 *                     stops after ~7s so it fits the tool's time limit — run again until "remaining: 0").
 * DR_STEP = 'page'  — updates dr.css / dr.js, creates or updates the "About Me" page from
 *                     elementor/about.json, points "About Me" in the header & footer menus at it and
 *                     adds "Gallery" (# until that page is designed) before "Contact".
 *
 * Idempotent: safe to run again.
 */

$dr_ref  = defined( 'DR_REF' ) ? DR_REF : 'main';
$dr_base = 'https://raw.githubusercontent.com/gajula-atarim/Dance-Reaction/' . $dr_ref . '/';
$step    = defined( 'DR_STEP' ) ? DR_STEP : 'page';
$log     = array();
$start   = microtime( true );

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

if ( ! get_current_user_id() ) {
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
	if ( $admins ) {
		wp_set_current_user( $admins[0]->ID );
	}
}

// Placeholder photos from the About design (Pexels, free to use). Swap them in the Media Library any time.
$images = array(
	'dr-about-hero.jpg'  => array( 'https://images.pexels.com/photos/1190298/pexels-photo-1190298.jpeg?auto=compress&cs=tinysrgb&w=1920', 'DJ playing to a crowd under stage lights' ),
	'dr-about-a.jpg'     => array( 'https://images.pexels.com/photos/10821303/pexels-photo-10821303.jpeg?auto=compress&cs=tinysrgb&w=1200', 'DJ mixing on the decks' ),
	'dr-about-b.jpg'     => array( 'https://images.pexels.com/photos/34009280/pexels-photo-34009280.jpeg?auto=compress&cs=tinysrgb&w=800', 'Hands on a DJ controller' ),
	'dr-about-story.jpg' => array( 'https://images.pexels.com/photos/5156606/pexels-photo-5156606.jpeg?auto=compress&cs=tinysrgb&w=1200', 'Vinyl records' ),
	'dr-about-c.jpg'     => array( 'https://images.pexels.com/photos/28123410/pexels-photo-28123410.jpeg?auto=compress&cs=tinysrgb&w=1200', 'DJ performing for a crowd' ),
	'dr-about-d.jpg'     => array( 'https://images.pexels.com/photos/5804898/pexels-photo-5804898.jpeg?auto=compress&cs=tinysrgb&w=1200', 'Party lights over the dance floor' ),
);

$find = function ( $name ) {
	$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_dr_source', 'meta_value' => $name, 'numberposts' => 1, 'fields' => 'ids' ) );
	return $found ? (int) $found[0] : 0;
};

$fetch = function ( $path ) use ( $dr_base ) {
	$res = wp_remote_get( $dr_base . $path, array( 'timeout' => 20 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		throw new Exception( 'Download failed: ' . $path );
	}
	return wp_remote_retrieve_body( $res );
};

try {
	if ( 'media' === $step ) {
		$remaining = 0;
		foreach ( $images as $name => $def ) {
			if ( $find( $name ) ) {
				continue;
			}
			if ( microtime( true ) - $start > 7 ) {
				++$remaining;
				continue;
			}
			$tmp = download_url( $def[0], 20 );
			if ( is_wp_error( $tmp ) ) {
				throw new Exception( 'Image download failed: ' . $name . ' ' . $tmp->get_error_message() );
			}
			$id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, $def[1] );
			if ( is_wp_error( $id ) ) {
				@unlink( $tmp );
				throw new Exception( 'Sideload failed: ' . $name . ' ' . $id->get_error_message() );
			}
			update_post_meta( $id, '_dr_source', $name );
			update_post_meta( $id, '_wp_attachment_image_alt', $def[1] );
			$log[] = "imported $name (#$id)";
		}
		$log[] = 'remaining: ' . $remaining;
		return implode( "\n", $log );
	}

	/* Page step. */
	foreach ( $images as $name => $def ) {
		if ( ! $find( $name ) ) {
			throw new Exception( "Run DR_STEP 'media' first ($name missing)." );
		}
	}

	// 1. Theme styles & scripts.
	$dir = get_theme_root() . '/dance-reaction-child';
	foreach ( array( 'assets/css/dr.css', 'assets/js/dr.js' ) as $file ) {
		$body = $fetch( 'theme/dance-reaction-child/' . $file );
		copy( $dir . '/' . $file, $dir . '/' . $file . '.bak' );
		file_put_contents( $dir . '/' . $file, $body );
	}
	$log[] = 'theme: dr.css, dr.js updated';

	// 2. About page.
	$media = array();
	foreach ( get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_dr_source', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
		$media[ get_post_meta( $id, '_dr_source', true ) ] = array( 'id' => $id, 'url' => wp_get_attachment_url( $id ) );
	}
	$swap = function ( $node ) use ( &$swap, $media ) {
		if ( is_array( $node ) ) {
			if ( isset( $node['id'], $node['url'] ) && is_string( $node['id'] ) && 0 === strpos( $node['id'], '__IMG__' ) ) {
				$name = substr( $node['id'], 7 );
				if ( isset( $media[ $name ] ) ) {
					$node['id']  = $media[ $name ]['id'];
					$node['url'] = $media[ $name ]['url'];
				}
				return $node;
			}
			foreach ( $node as $k => $v ) {
				$node[ $k ] = $swap( $v );
			}
		}
		return $node;
	};
	$data = json_decode( $fetch( 'elementor/about.json' ), true );
	if ( ! is_array( $data ) ) {
		throw new Exception( 'Bad JSON: about.json' );
	}

	$about_id = (int) get_option( 'dr_about_page_id' );
	if ( ! $about_id || ! get_post( $about_id ) ) {
		$about_id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'About Me', 'post_name' => 'about', 'post_status' => 'publish' ) );
		if ( is_wp_error( $about_id ) || ! $about_id ) {
			throw new Exception( 'Could not create the About page.' );
		}
		update_option( 'dr_about_page_id', $about_id );
	}
	wp_update_post( array( 'ID' => $about_id, 'post_status' => 'publish' ) );
	update_post_meta( $about_id, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $about_id, '_elementor_edit_mode', 'builder' );
	\Elementor\Plugin::instance()->documents->get( $about_id, false )->save(
		array(
			'elements' => $swap( $data ),
			'settings' => array( 'template' => 'elementor_header_footer', 'hide_title' => 'yes' ),
		)
	);
	$log[] = 'about page: #' . $about_id . ' ' . get_permalink( $about_id );

	// 3. Menus: "About Me" -> About page; "Gallery" (#) before "Contact".
	foreach ( array( 'Header Menu', 'Footer Menu' ) as $menu_name ) {
		$menu = wp_get_nav_menu_object( $menu_name );
		if ( ! $menu ) {
			$log[] = "menu '$menu_name' not found";
			continue;
		}
		$items = wp_get_nav_menu_items( $menu->term_id ) ?: array();
		$pos   = 0;
		$has_gallery = false;
		foreach ( $items as $item ) {
			if ( 'Gallery' === $item->title ) {
				$has_gallery = true;
			}
		}
		foreach ( $items as $item ) {
			if ( 'Contact' === $item->title && ! $has_gallery ) {
				wp_update_nav_menu_item( $menu->term_id, 0, array(
					'menu-item-title'    => 'Gallery',
					'menu-item-url'      => '#',
					'menu-item-type'     => 'custom',
					'menu-item-status'   => 'publish',
					'menu-item-position' => ++$pos,
				) );
			}
			$args = array(
				'menu-item-title'    => $item->title,
				'menu-item-url'      => $item->url,
				'menu-item-type'     => $item->type,
				'menu-item-object'   => $item->object,
				'menu-item-object-id' => $item->object_id,
				'menu-item-status'   => 'publish',
				'menu-item-position' => ++$pos,
			);
			if ( 'About Me' === $item->title ) {
				$args['menu-item-type']      = 'post_type';
				$args['menu-item-object']    = 'page';
				$args['menu-item-object-id'] = $about_id;
				$args['menu-item-url']       = '';
			}
			wp_update_nav_menu_item( $menu->term_id, $item->ID, $args );
		}
		$titles = wp_list_pluck( wp_get_nav_menu_items( $menu->term_id ) ?: array(), 'title' );
		$log[]  = "$menu_name: " . implode( ' · ', $titles );
	}

	// 4. Caches.
	\Elementor\Plugin::instance()->files_manager->clear_cache();
	delete_post_meta_by_key( '_elementor_element_cache' );
	do_action( 'cache_enabler_clear_complete_cache' );
	$log[] = 'caches cleared';
} catch ( Exception $e ) {
	$log[] = 'ERROR: ' . $e->getMessage();
}

return implode( "\n", $log );
