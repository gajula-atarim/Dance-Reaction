<?php
/**
 * The Music, Equipment and Contact pages for Dance Reaction (run through Atarim's execute-php
 * with DR_REF and DR_STEP defined).
 *
 * DR_STEP = 'media' — imports the placeholder photos for these pages (skips ones already imported;
 *                     stops after ~7s — run again until "remaining: 0").
 * DR_STEP = 'pages' — updates the theme files, creates/updates The Music, Equipment and Contact
 *                     pages, refreshes Home ("About Dance Reaction →" → About page), About + Gallery (buttons now go to the Contact page, "View
 *                     gallery" added) and the UAE Site Header ("Book now" → Contact), and points
 *                     The Music / Equipment / Contact in the header & footer menus at the new pages.
 *                     Stops after ~9s if needed — run again until "remaining: 0".
 *
 * Idempotent: safe to run again.
 */

$dr_ref  = defined( 'DR_REF' ) ? DR_REF : 'main';
$dr_base = 'https://raw.githubusercontent.com/gajula-atarim/Dance-Reaction/' . $dr_ref . '/';
$step    = defined( 'DR_STEP' ) ? DR_STEP : 'pages';
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

// Placeholder photos from the designs (Pexels, free to use). Swap them in the Media Library any time.
$px     = 'https://images.pexels.com/photos/%1$s/pexels-photo-%1$s.jpeg?auto=compress&cs=tinysrgb&w=%2$s';
$images = array(
	'dr-music-hero.jpg'   => array( sprintf( $px, '2111015', 1920 ), 'Dance floor under party lights' ),
	'dr-equip-hero.jpg'   => array( sprintf( $px, '3784566', 1920 ), 'DJ mixer and controls' ),
	'dr-equip-std.jpg'    => array( sprintf( $px, '1763075', 1200 ), 'DJ equipment set up for an event' ),
	'dr-equip-a.jpg'      => array( sprintf( $px, '1649691', 1200 ), 'Speakers and lighting at a venue' ),
	'dr-contact-hero.jpg' => array( sprintf( $px, '1105666', 1920 ), 'Party crowd with hands in the air' ),
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

	foreach ( array_keys( $images ) as $name ) {
		if ( ! $find( $name ) ) {
			throw new Exception( "Run DR_STEP 'media' first ($name missing)." );
		}
	}

	// 1. Theme files (contact form layout, styles, effects).
	$dir = get_theme_root() . '/dance-reaction-child';
	foreach ( array( 'inc/booking-form.php', 'assets/css/dr.css', 'assets/js/dr.js' ) as $file ) {
		$body = $fetch( 'theme/dance-reaction-child/' . $file );
		copy( $dir . '/' . $file, $dir . '/' . $file . '.bak' );
		file_put_contents( $dir . '/' . $file, $body );
		if ( function_exists( 'opcache_invalidate' ) ) {
			opcache_invalidate( $dir . '/' . $file, true );
		}
	}
	$log[] = 'theme: booking-form.php, dr.css, dr.js updated';

	// 2. Elementor data.
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
	$elementor = \Elementor\Plugin::instance();

	// title, slug, option, json  — new pages first, then the existing ones that changed.
	$targets   = array(
		array( 'The Music', 'the-music', 'dr_music_page_id', 'music.json' ),
		array( 'Equipment', 'equipment', 'dr_equipment_page_id', 'equipment.json' ),
		array( 'Contact', 'contact', 'dr_contact_page_id', 'contact.json' ),
		array( null, null, 'dr_home_page_id', 'home.json' ),
		array( null, null, 'dr_about_page_id', 'about.json' ),
		array( null, null, 'dr_gallery_page_id', 'gallery.json' ),
		array( null, null, 'dr_hfe_header_id', 'hfe-header.json' ),
	);
	$remaining = 0;
	$pages     = array();
	foreach ( $targets as $t ) {
		list( $title, $slug, $option, $file ) = $t;
		$id = (int) get_option( $option );
		if ( $title ) {
			$pages[ $title ] = $id;
		}
		if ( $id && get_post( $id ) && (string) get_post_meta( $id, '_dr_ref_' . $file, true ) === $dr_ref ) {
			continue; // Already deployed from this commit.
		}
		if ( microtime( true ) - $start > 9 ) {
			++$remaining;
			continue;
		}
		$data = json_decode( $fetch( 'elementor/' . $file ), true );
		if ( ! is_array( $data ) ) {
			throw new Exception( 'Bad JSON: ' . $file );
		}
		if ( $title && ( ! $id || ! get_post( $id ) ) ) {
			$id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => $title, 'post_name' => $slug, 'post_status' => 'publish' ) );
			if ( is_wp_error( $id ) || ! $id ) {
				throw new Exception( 'Could not create page: ' . $title );
			}
			update_option( $option, $id );
			$pages[ $title ] = $id;
		}
		if ( ! $id ) {
			throw new Exception( 'Missing target for ' . $file );
		}
		$settings = array();
		if ( 'hfe-header.json' !== $file ) {
			wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
			update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
			$settings = array( 'template' => 'elementor_header_footer', 'hide_title' => 'yes' );
		}
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		$elementor->documents->get( $id, false )->save( array_filter( array( 'elements' => $swap( $data ), 'settings' => $settings ) ) );
		update_post_meta( $id, '_dr_ref_' . $file, $dr_ref );
		$log[] = "$file -> #$id " . ( 'hfe-header.json' === $file ? '' : get_permalink( $id ) );
	}

	// 3. Menus: The Music / Equipment / Contact -> their pages.
	foreach ( array( 'Header Menu', 'Footer Menu' ) as $menu_name ) {
		$menu = wp_get_nav_menu_object( $menu_name );
		if ( ! $menu ) {
			$log[] = "menu '$menu_name' not found";
			continue;
		}
		foreach ( wp_get_nav_menu_items( $menu->term_id ) ?: array() as $item ) {
			if ( empty( $pages[ $item->title ] ) || ( 'post_type' === $item->type && (int) $item->object_id === $pages[ $item->title ] ) ) {
				continue;
			}
			wp_update_nav_menu_item( $menu->term_id, $item->ID, array(
				'menu-item-title'     => $item->title,
				'menu-item-type'      => 'post_type',
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $pages[ $item->title ],
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $item->menu_order,
			) );
		}
		$log[] = "$menu_name: " . implode( ' · ', array_map( function ( $i ) {
			return $i->title . ( '#' === $i->url ? ' (#)' : '' );
		}, wp_get_nav_menu_items( $menu->term_id ) ?: array() ) );
	}

	// 4. Caches.
	$elementor->files_manager->clear_cache();
	delete_post_meta_by_key( '_elementor_element_cache' );
	do_action( 'cache_enabler_clear_complete_cache' );
	$log[] = 'caches cleared';
	$log[] = 'remaining: ' . $remaining;
} catch ( Exception $e ) {
	$log[] = 'ERROR: ' . $e->getMessage();
}

return implode( "\n", $log );
