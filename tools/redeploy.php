<?php
/**
 * Redeploy theme files and the Home page Elementor data from GitHub without touching media, menus or settings.
 * (Header/footer are UAE templates: use tools/hfe-setup.php.)
 * Run through Atarim's execute-php with DR_REF defined as the commit to deploy.
 */

$base = 'https://raw.githubusercontent.com/gajula-atarim/Dance-Reaction/' . ( defined( 'DR_REF' ) ? DR_REF : 'main' ) . '/';
$log  = array();

if ( ! get_current_user_id() ) {
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
	if ( $admins ) {
		wp_set_current_user( $admins[0]->ID );
	}
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
	foreach ( array( 'style.css', 'functions.php', 'header.php', 'footer.php', 'inc/booking-form.php', 'inc/template-parts.php', 'assets/css/dr.css', 'assets/js/dr.js' ) as $file ) {
		file_put_contents( $dir . '/' . $file, $fetch( 'theme/dance-reaction-child/' . $file ) );
	}
	$log[] = 'theme files updated';

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
	$targets   = array(
		'home.json'   => (int) get_option( 'dr_home_page_id' ),
	);
	foreach ( $targets as $file => $id ) {
		$data = json_decode( $fetch( 'elementor/' . $file ), true );
		if ( ! is_array( $data ) || ! $id ) {
			throw new Exception( 'Bad data or missing target for ' . $file );
		}
		$elementor->documents->get( $id, false )->save( array( 'elements' => $swap( $data ) ) );
		$log[] = "$file -> $id";
	}

	$elementor->files_manager->clear_cache();
	wp_cache_flush();
} catch ( Exception $e ) {
	$log[] = 'ERROR: ' . $e->getMessage();
}

return implode( "\n", $log );
