<?php
/**
 * Gallery page for Dance Reaction (run through Atarim's execute-php with DR_REF defined).
 *
 * Imports the hero photo (if missing), updates dr.css / dr.js, creates or updates the "Gallery"
 * page from elementor/gallery.json (the photo wall uses the 20 event photos already in the
 * Media Library) and points "Gallery" in the header & footer menus at it.
 *
 * Idempotent: safe to run again.
 */

$dr_ref  = defined( 'DR_REF' ) ? DR_REF : 'main';
$dr_base = 'https://raw.githubusercontent.com/gajula-atarim/Dance-Reaction/' . $dr_ref . '/';
$log     = array();

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

if ( ! get_current_user_id() ) {
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
	if ( $admins ) {
		wp_set_current_user( $admins[0]->ID );
	}
}

$fetch = function ( $path ) use ( $dr_base ) {
	$res = wp_remote_get( $dr_base . $path, array( 'timeout' => 20 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		throw new Exception( 'Download failed: ' . $path );
	}
	return wp_remote_retrieve_body( $res );
};

try {
	// 1. Hero photo (placeholder from the Gallery design, Pexels — swap it in the Media Library any time).
	$hero_name = 'dr-gallery-hero.jpg';
	$hero_alt  = 'Crowd celebrating under confetti';
	$found     = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_dr_source', 'meta_value' => $hero_name, 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( ! $found ) {
		$tmp = download_url( 'https://images.pexels.com/photos/1540406/pexels-photo-1540406.jpeg?auto=compress&cs=tinysrgb&w=1920', 20 );
		if ( is_wp_error( $tmp ) ) {
			throw new Exception( 'Hero download failed: ' . $tmp->get_error_message() );
		}
		$id = media_handle_sideload( array( 'name' => $hero_name, 'tmp_name' => $tmp ), 0, $hero_alt );
		if ( is_wp_error( $id ) ) {
			@unlink( $tmp );
			throw new Exception( 'Hero sideload failed: ' . $id->get_error_message() );
		}
		update_post_meta( $id, '_dr_source', $hero_name );
		update_post_meta( $id, '_wp_attachment_image_alt', $hero_alt );
		$log[] = "imported $hero_name (#$id)";
	}

	// The photo wall references attachments 80–99 directly; make sure they are still there.
	$missing = array();
	foreach ( range( 80, 99 ) as $att ) {
		if ( 'attachment' !== get_post_type( $att ) ) {
			$missing[] = $att;
		}
	}
	if ( $missing ) {
		throw new Exception( 'Gallery photos missing from the Media Library: #' . implode( ', #', $missing ) );
	}

	// 2. Theme styles & scripts.
	$dir = get_theme_root() . '/dance-reaction-child';
	foreach ( array( 'assets/css/dr.css', 'assets/js/dr.js' ) as $file ) {
		$body = $fetch( 'theme/dance-reaction-child/' . $file );
		copy( $dir . '/' . $file, $dir . '/' . $file . '.bak' );
		file_put_contents( $dir . '/' . $file, $body );
	}
	$log[] = 'theme: dr.css, dr.js updated';

	// 3. Gallery page.
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
	$data = json_decode( $fetch( 'elementor/gallery.json' ), true );
	if ( ! is_array( $data ) ) {
		throw new Exception( 'Bad JSON: gallery.json' );
	}

	$page_id = (int) get_option( 'dr_gallery_page_id' );
	if ( ! $page_id || ! get_post( $page_id ) ) {
		$page_id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Gallery', 'post_name' => 'gallery', 'post_status' => 'publish' ) );
		if ( is_wp_error( $page_id ) || ! $page_id ) {
			throw new Exception( 'Could not create the Gallery page.' );
		}
		update_option( 'dr_gallery_page_id', $page_id );
	}
	wp_update_post( array( 'ID' => $page_id, 'post_status' => 'publish' ) );
	update_post_meta( $page_id, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
	\Elementor\Plugin::instance()->documents->get( $page_id, false )->save(
		array(
			'elements' => $swap( $data ),
			'settings' => array( 'template' => 'elementor_header_footer', 'hide_title' => 'yes' ),
		)
	);
	$log[] = 'gallery page: #' . $page_id . ' ' . get_permalink( $page_id );

	// 4. Menus: "Gallery" -> Gallery page.
	foreach ( array( 'Header Menu', 'Footer Menu' ) as $menu_name ) {
		$menu = wp_get_nav_menu_object( $menu_name );
		if ( ! $menu ) {
			$log[] = "menu '$menu_name' not found";
			continue;
		}
		foreach ( wp_get_nav_menu_items( $menu->term_id ) ?: array() as $item ) {
			if ( 'Gallery' !== $item->title ) {
				continue;
			}
			wp_update_nav_menu_item( $menu->term_id, $item->ID, array(
				'menu-item-title'     => 'Gallery',
				'menu-item-type'      => 'post_type',
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page_id,
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $item->menu_order,
			) );
		}
		$items = wp_get_nav_menu_items( $menu->term_id ) ?: array();
		$log[] = "$menu_name: " . implode( ' · ', array_map( function ( $i ) {
			return $i->title . ( '#' === $i->url ? ' (#)' : '' );
		}, $items ) );
	}

	// 5. Caches.
	\Elementor\Plugin::instance()->files_manager->clear_cache();
	delete_post_meta_by_key( '_elementor_element_cache' );
	do_action( 'cache_enabler_clear_complete_cache' );
	$log[] = 'caches cleared';
} catch ( Exception $e ) {
	$log[] = 'ERROR: ' . $e->getMessage();
}

return implode( "\n", $log );
