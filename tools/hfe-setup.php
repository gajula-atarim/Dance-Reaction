<?php
/**
 * Create / update the Ultimate Addons for Elementor (header-footer-elementor) header and
 * footer templates from elementor/hfe-*.json, shown on the entire site.
 * Also refreshes the child theme files. Idempotent. Define DR_REF as the commit to deploy.
 */

$base = 'https://raw.githubusercontent.com/gajula-atarim/Dance-Reaction/' . ( defined( 'DR_REF' ) ? DR_REF : 'main' ) . '/';
$log  = array();

if ( ! get_current_user_id() ) {
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
	if ( $admins ) {
		wp_set_current_user( $admins[0]->ID );
	}
}
if ( ! post_type_exists( 'elementor-hf' ) ) {
	return 'Ultimate Addons for Elementor is not active.';
}

$fetch = function ( $path ) use ( $base ) {
	$res = wp_remote_get( $base . $path, array( 'timeout' => 30 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		throw new Exception( 'Download failed: ' . $path );
	}
	return wp_remote_retrieve_body( $res );
};

try {
	$dir = get_theme_root() . '/dance-reaction-child';
	foreach ( array( 'functions.php', 'inc/template-parts.php', 'assets/css/dr.css', 'assets/js/dr.js' ) as $file ) {
		file_put_contents( $dir . '/' . $file, $fetch( 'theme/dance-reaction-child/' . $file ) );
	}
	$log[] = 'theme files updated';

	$elementor = \Elementor\Plugin::instance();
	$cpt       = (array) get_option( 'elementor_cpt_support', array( 'page', 'post' ) );
	if ( ! in_array( 'elementor-hf', $cpt, true ) ) {
		$cpt[] = 'elementor-hf';
		update_option( 'elementor_cpt_support', $cpt );
	}

	foreach ( array(
		'type_header' => array( 'Site Header (UAE)', 'dr_hfe_header_id', 'hfe-header.json' ),
		'type_footer' => array( 'Site Footer (UAE)', 'dr_hfe_footer_id', 'hfe-footer.json' ),
	) as $type => $def ) {
		list( $title, $option, $file ) = $def;
		$id = (int) get_option( $option );
		if ( ! $id || 'elementor-hf' !== get_post_type( $id ) ) {
			$id = wp_insert_post( array( 'post_type' => 'elementor-hf', 'post_title' => $title, 'post_status' => 'publish' ) );
			update_option( $option, $id );
		}
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish', 'post_title' => $title ) );
		update_post_meta( $id, 'ehf_template_type', $type );
		update_post_meta( $id, 'ehf_target_include_locations', array( 'rule' => array( 'basic-global' ), 'specific' => array() ) );
		update_post_meta( $id, 'ehf_target_exclude_locations', array() );
		update_post_meta( $id, 'ehf_target_user_roles', array( 'all' ) );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_wp_page_template', 'elementor_canvas' );

		$data = json_decode( $fetch( 'elementor/' . $file ), true );
		if ( ! is_array( $data ) ) {
			throw new Exception( 'Bad JSON: ' . $file );
		}
		$elementor->documents->get( $id, false )->save( array( 'elements' => $data ) );
		$log[] = "$title -> $id";
	}

	$elementor->files_manager->clear_cache();
	wp_cache_flush();
} catch ( Exception $e ) {
	$log[] = 'ERROR: ' . $e->getMessage();
}

return implode( "\n", $log );
