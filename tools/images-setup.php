<?php
/**
 * Replaces the repeated photos in The Music "Your music, your guests" and Equipment "My gear" sections
 * (run through Atarim's execute-php with DR_REF defined).
 *
 * Imports the new Pexels photos (skips ones already imported), then re-saves The Music and Equipment
 * pages from elementor/music.json and elementor/equipment.json. Stops after ~7s if needed — run again
 * until "remaining: 0".
 *
 * Idempotent: safe to run again.
 */

$dr_ref  = defined( 'DR_REF' ) ? DR_REF : 'main';
$dr_base = 'https://raw.githubusercontent.com/gajula-atarim/Dance-Reaction/' . $dr_ref . '/';
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

// Placeholder photos (Pexels, free to use). Swap them in the Media Library any time.
$px     = 'https://images.pexels.com/photos/%1$s/pexels-photo-%1$s.jpeg?auto=compress&cs=tinysrgb&w=%2$s';
$images = array(
	'dr-x-requests.jpg'  => array( sprintf( $px, '18433817', 1200 ), 'A guest singing at a party' ),
	'dr-x-turntable.jpg' => array( sprintf( $px, '164853', 800 ), 'A record spinning on a turntable' ),
	'dr-x-intro.jpg'     => array( sprintf( $px, '9005506', 1200 ), 'Hands on a DJ mixer in the smoke and lights' ),
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
	// 1. Photos.
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

	// 2. Pages (once every photo is in).
	$targets = array( 'dr_music_page_id' => 'music.json', 'dr_equipment_page_id' => 'equipment.json' );
	if ( ! $remaining ) {
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
		foreach ( $targets as $option => $file ) {
			$id = (int) get_option( $option );
			if ( ! $id || ! get_post( $id ) ) {
				throw new Exception( 'Missing page for ' . $file );
			}
			if ( (string) get_post_meta( $id, '_dr_ref_' . $file, true ) === $dr_ref ) {
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
			$elementor->documents->get( $id, false )->save( array(
				'elements' => $swap( $data ),
				'settings' => array( 'template' => 'elementor_header_footer', 'hide_title' => 'yes' ),
			) );
			update_post_meta( $id, '_dr_ref_' . $file, $dr_ref );
			$log[] = "$file -> #$id " . get_permalink( $id );
		}
		$elementor->files_manager->clear_cache();
		delete_post_meta_by_key( '_elementor_element_cache' );
		do_action( 'cache_enabler_clear_complete_cache' );
		$log[] = 'caches cleared';
	} else {
		$remaining += count( $targets );
	}
	$log[] = 'remaining: ' . $remaining;
} catch ( Exception $e ) {
	$log[] = 'ERROR: ' . $e->getMessage();
}

return implode( "\n", $log );
