<?php
/**
 * Site-wide speed settings for Dance Reaction (run through Atarim's execute-php with DR_REF defined).
 *
 * 1. Updates the theme's functions.php (hero preload on every page, right-sized background photos on
 *    save, right-sized Gallery tiles). Restores the old file if a page check fails.
 * 2. Re-saves Home, About, Gallery, The Music, Equipment and Contact so their background photos
 *    use the right-sized copies.
 * 3. Turns on Cache Enabler's HTML minification and pre-compressed (gzip) cache, then clears caches.
 *
 * Stops after ~9s if needed — run again until "remaining: 0". Idempotent: safe to run again.
 */

$dr_ref  = defined( 'DR_REF' ) ? DR_REF : 'main';
$dr_base = 'https://raw.githubusercontent.com/gajula-atarim/Dance-Reaction/' . $dr_ref . '/';
$log     = array();
$start   = microtime( true );

if ( ! get_current_user_id() ) {
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
	if ( $admins ) {
		wp_set_current_user( $admins[0]->ID );
	}
}

$check = function ( $path ) {
	$res = wp_remote_get( home_url( $path . '?drcheck=' . time() ), array( 'timeout' => 20 ) );
	$h   = wp_remote_retrieve_body( $res );
	$ok  = 200 === wp_remote_retrieve_response_code( $res ) && false === strpos( $h, 'critical error' ) && false !== strpos( $h, 'dr-site' );
	return array( $ok, $h );
};

try {
	$dir  = get_stylesheet_directory();
	$file = $dir . '/functions.php';

	// 1. Theme functions.
	if ( false === strpos( (string) file_get_contents( $file ), 'dr_background_size' ) ) {
		$res = wp_remote_get( $dr_base . 'theme/dance-reaction-child/functions.php', array( 'timeout' => 20 ) );
		$new = wp_remote_retrieve_body( $res );
		if ( 200 !== wp_remote_retrieve_response_code( $res ) || false === strpos( $new, 'dr_background_size' ) ) {
			throw new Exception( 'Download failed: functions.php' );
		}
		$old = file_get_contents( $file );
		copy( $file, $file . '.bak' );
		file_put_contents( $file, $new );
		if ( function_exists( 'opcache_invalidate' ) ) {
			opcache_invalidate( $file, true );
		}
		list( $ok ) = $check( '/' );
		if ( ! $ok ) {
			file_put_contents( $file, $old );
			if ( function_exists( 'opcache_invalidate' ) ) {
				opcache_invalidate( $file, true );
			}
			throw new Exception( 'Home page check failed — functions.php restored' );
		}
		$log[] = 'theme: functions.php updated';
		$log[] = 'remaining: 1 (run again to re-save the pages with the new code loaded)';
		return implode( "\n", $log );
	}

	// 2. Re-save the pages (the save filter right-sizes their background photos).
	$remaining = 0;
	$elementor = \Elementor\Plugin::instance();
	foreach ( array( 'dr_home_page_id', 'dr_about_page_id', 'dr_gallery_page_id', 'dr_music_page_id', 'dr_equipment_page_id', 'dr_contact_page_id' ) as $option ) {
		$id = (int) get_option( $option );
		if ( ! $id || ! get_post( $id ) ) {
			$log[] = "$option: page not found";
			continue;
		}
		if ( (string) get_post_meta( $id, '_dr_perf_ref', true ) === $dr_ref ) {
			continue;
		}
		if ( microtime( true ) - $start > 9 ) {
			++$remaining;
			continue;
		}
		$doc = $elementor->documents->get( $id, false );
		$doc->save( array( 'elements' => $doc->get_elements_data() ) );
		update_post_meta( $id, '_dr_perf_ref', $dr_ref );
		$sized = preg_match_all( '#"url":"[^"]+-(768|1024|1536)x\d+\.\w+"#', (string) get_post_meta( $id, '_elementor_data', true ) );
		$log[] = get_the_title( $id ) . ": re-saved, right-sized backgrounds: $sized";
	}

	if ( ! $remaining ) {
		// 3. Page cache: minify HTML (not inline CSS/JS) and store a gzip copy.
		$ce = get_option( 'cache_enabler' );
		if ( is_array( $ce ) && ( empty( $ce['minify_html'] ) || empty( $ce['compress_cache'] ) ) ) {
			$ce['minify_html']    = 1;
			$ce['compress_cache'] = 1;
			update_option( 'cache_enabler', $ce );
			$log[] = 'cache enabler: HTML minify + gzip cache on';
		}
		$elementor->files_manager->clear_cache();
		delete_post_meta_by_key( '_elementor_element_cache' );
		do_action( 'cache_enabler_clear_complete_cache' );
		$log[] = 'caches cleared';

		foreach ( array( '/', '/about/', '/gallery/', '/the-music/', '/equipment/', '/contact/' ) as $path ) {
			list( $ok, $h ) = $check( $path );
			$log[] = sprintf( '%s %s; hero preload=%d, lazy imgs=%d, gallery sizes=%d', $path, $ok ? 'OK' : 'FAILED',
				preg_match_all( '#rel="preload" as="image"#', $h ), substr_count( $h, 'loading="lazy"' ), substr_count( $h, '33vw, 420px' ) );
		}
	}
	$log[] = 'remaining: ' . $remaining;
} catch ( Exception $e ) {
	$log[] = 'ERROR: ' . $e->getMessage();
}

return implode( "\n", $log );
