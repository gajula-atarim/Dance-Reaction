<?php
/**
 * Re-deploys the Home page and the theme stylesheet (run through Atarim's execute-php with DR_REF defined).
 *
 * Updates dr.css, re-saves Home from elementor/home.json (photos swapped for the Media Library
 * attachments tagged with _dr_source), clears caches and checks that Home renders.
 *
 * Idempotent: safe to run again.
 */

$dr_ref  = defined( 'DR_REF' ) ? DR_REF : 'main';
$dr_base = 'https://raw.githubusercontent.com/gajula-atarim/Dance-Reaction/' . $dr_ref . '/';
$log     = array();

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
	$css  = $fetch( 'theme/dance-reaction-child/assets/css/dr.css' );
	$data = json_decode( $fetch( 'elementor/home.json' ), true );
	if ( ! is_array( $data ) ) {
		throw new Exception( 'Bad JSON: home.json' );
	}

	// 1. Stylesheet.
	$file = get_stylesheet_directory() . '/assets/css/dr.css';
	copy( $file, $file . '.bak' );
	file_put_contents( $file, $css );
	$log[] = 'theme: dr.css updated';

	// 2. Home page.
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
	$home_id = (int) get_option( 'dr_home_page_id' );
	if ( ! $home_id || ! get_post( $home_id ) ) {
		throw new Exception( 'Home page not found' );
	}
	\Elementor\Plugin::instance()->documents->get( $home_id, false )->save( array( 'elements' => $swap( $data ) ) );
	update_post_meta( $home_id, '_dr_ref_home.json', $dr_ref );
	$log[] = 'home.json -> #' . $home_id;

	// 3. Caches + check.
	\Elementor\Plugin::instance()->files_manager->clear_cache();
	delete_post_meta_by_key( '_elementor_element_cache' );
	do_action( 'cache_enabler_clear_complete_cache' );
	$log[] = 'caches cleared';
	$res = wp_remote_get( home_url( '/?drcheck=' . time() ), array( 'timeout' => 20 ) );
	$h   = wp_remote_retrieve_body( $res );
	$log[] = sprintf( 'home check: HTTP %d, rhythm line=%s, Preston=%s, new welcome text=%s, hero preload=%d',
		wp_remote_retrieve_response_code( $res ),
		false !== strpos( $h, 'dr-hero-tag--rhythm' ) ? 'yes' : 'NO',
		false !== strpos( $h, 'Preston, VIC 3072' ) ? 'yes' : 'NO',
		false !== strpos( $h, 'social club presentation evening' ) ? 'yes' : 'NO',
		preg_match_all( '#rel="preload" as="image"#', $h ) );
} catch ( Exception $e ) {
	$log[] = 'ERROR: ' . $e->getMessage();
}

return implode( "\n", $log );
