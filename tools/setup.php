<?php
/**
 * One-off site setup for Dance Reaction (run through Atarim's execute-php, or `wp eval-file`).
 *
 * Idempotent: safe to run again — it updates the same theme files, media, templates,
 * page and menus instead of creating duplicates.
 *
 * Requires Elementor (active) and Hello Elementor (installed).
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

if ( ! did_action( 'elementor/loaded' ) ) {
	return 'Elementor is not active — activate it first.';
}
if ( ! wp_get_theme( 'hello-elementor' )->exists() ) {
	return 'Hello Elementor is not installed — install it first.';
}

$fetch = function ( $path ) use ( $dr_base ) {
	$res = wp_remote_get( $dr_base . $path, array( 'timeout' => 30 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		throw new Exception( 'Download failed: ' . $path . ' ' . ( is_wp_error( $res ) ? $res->get_error_message() : wp_remote_retrieve_response_code( $res ) ) );
	}
	return wp_remote_retrieve_body( $res );
};

try {
	/* 1. Child theme files. */
	$theme_dir = get_theme_root() . '/dance-reaction-child';
	$files     = array( 'style.css', 'functions.php', 'header.php', 'footer.php', 'screenshot.jpg', 'inc/booking-form.php', 'inc/template-parts.php', 'assets/css/dr.css', 'assets/js/dr.js' );
	foreach ( $files as $file ) {
		$body = $fetch( 'theme/dance-reaction-child/' . $file );
		wp_mkdir_p( dirname( $theme_dir . '/' . $file ) );
		file_put_contents( $theme_dir . '/' . $file, $body );
	}
	wp_clean_themes_cache();
	if ( 'dance-reaction-child' !== get_stylesheet() ) {
		switch_theme( 'dance-reaction-child' );
	}
	$log[] = 'theme: ' . count( $files ) . ' files, active=' . get_stylesheet();

	/* 2. Media library images (matched by _dr_source so re-runs reuse them). */
	$images = array(
		'dr-home-hero.jpg'          => 'Crowd dancing under confetti and lights',
		'dr-home-dj.jpg'            => 'DJ playing a set under neon lights',
		'dr-home-dj-b.jpg'          => 'DJ at the decks in a neon-lit club',
		'dr-home-why-1.jpg'         => 'DJ performing in a red neon club',
		'dr-home-why-2.jpg'         => 'Birthday celebration with friends',
		'dr-home-why-3.jpg'         => 'DJ booth under lasers',
		'dr-home-why-4.jpg'         => 'Couple dancing at their wedding',
		'dr-home-price-a.jpg'       => 'DJ under blue lasers',
		'dr-home-price-b.jpg'       => 'Crowd dancing under confetti',
		'dr-home-how-a.jpg'         => 'Couple\'s first dance at a wedding reception',
		'dr-home-how-b.jpg'         => 'Guests at a corporate dinner',
		'dr-home-ev-wedding.jpg'    => 'Wedding first dance',
		'dr-home-ev-corporate.jpg'  => 'Corporate dinner guests',
		'dr-home-ev-birthday.jpg'   => 'Birthday party with friends',
		'dr-home-g1.jpg'            => 'DJ booth under lasers',
		'dr-home-g2.jpg'            => 'DJ performing in a neon club',
		'dr-home-g3.jpg'            => 'Birthday celebration with sparklers',
		'dr-home-g5.jpg'            => 'Party guests dancing',
	);
	$media = array();
	foreach ( $images as $name => $alt ) {
		$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_dr_source', 'meta_value' => $name, 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( $found ) {
			$id = (int) $found[0];
		} else {
			$tmp = download_url( $dr_base . 'assets/images/' . $name, 60 );
			if ( is_wp_error( $tmp ) ) {
				throw new Exception( 'Image download failed: ' . $name . ' ' . $tmp->get_error_message() );
			}
			$id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, $alt );
			if ( is_wp_error( $id ) ) {
				@unlink( $tmp );
				throw new Exception( 'Sideload failed: ' . $name . ' ' . $id->get_error_message() );
			}
			update_post_meta( $id, '_dr_source', $name );
			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		}
		$media[ $name ] = array( 'id' => $id, 'url' => wp_get_attachment_url( $id ) );
	}
	$log[] = 'media: ' . count( $media ) . ' images';

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
	$load = function ( $file ) use ( $fetch, $swap ) {
		$data = json_decode( $fetch( 'elementor/' . $file ), true );
		if ( ! is_array( $data ) ) {
			throw new Exception( 'Bad JSON: ' . $file );
		}
		return $swap( $data );
	};

	$elementor = \Elementor\Plugin::instance();

	/* 3. Header & footer: built with Ultimate Addons for Elementor — run tools/hfe-setup.php after this. */

	/* 4. Home page. */
	$home_id = (int) get_option( 'dr_home_page_id' );
	if ( ! $home_id || ! get_post( $home_id ) ) {
		$home_id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Home', 'post_name' => 'home', 'post_status' => 'publish' ) );
		update_option( 'dr_home_page_id', $home_id );
	}
	wp_update_post( array( 'ID' => $home_id, 'post_status' => 'publish' ) );
	update_post_meta( $home_id, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $home_id, '_elementor_edit_mode', 'builder' );
	$elementor->documents->get( $home_id, false )->save(
		array(
			'elements' => $load( 'home.json' ),
			'settings' => array( 'template' => 'elementor_header_footer', 'hide_title' => 'yes' ),
		)
	);
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home_id );
	$log[] = "home page: $home_id";

	/* 5. Menus. */
	$menus = array(
		'primary' => array( 'Header Menu', array( 'Home' => '/', 'About Me' => '#', 'The Music' => '#', 'Equipment' => '#', 'Contact' => '#' ) ), // '#' until those pages are designed
		'footer'  => array( 'Footer Menu', array( 'Home Page' => '/', 'About Me' => '/#welcome', 'The Music' => '/#why', 'Equipment' => '/#how', 'Contact' => '/#enquiry', 'Site Map' => '#' ) ),
		'legal'   => array( 'Footer Legal', array( 'Standard Terms & Conditions of Hire' => '#', 'Privacy Statement' => '#' ) ),
	);
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	foreach ( $menus as $loc => $def ) {
		$menu = wp_get_nav_menu_object( $def[0] );
		$mid  = $menu ? $menu->term_id : wp_create_nav_menu( $def[0] );
		foreach ( wp_get_nav_menu_items( $mid ) ?: array() as $item ) {
			wp_delete_post( $item->ID, true );
		}
		$pos = 0;
		foreach ( $def[1] as $label => $url ) {
			wp_update_nav_menu_item( $mid, 0, array(
				'menu-item-title'    => $label,
				'menu-item-url'      => '#' === $url ? '#' : home_url( $url ),
				'menu-item-status'   => 'publish',
				'menu-item-type'     => 'custom',
				'menu-item-position' => ++$pos,
			) );
		}
		$locations[ $loc ] = $mid;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
	$log[] = 'menus: ' . implode( ', ', array_keys( $menus ) );

	/* 6. Elementor site settings (global colours & fonts) and options. */
	update_option( 'elementor_disable_color_schemes', 'yes' );
	update_option( 'elementor_disable_typography_schemes', 'yes' );
	update_option( 'elementor_experiment-container', 'active' );
	$cpt = (array) get_option( 'elementor_cpt_support', array( 'page', 'post' ) );
	update_option( 'elementor_cpt_support', array_values( array_unique( array_merge( $cpt, array( 'page', 'post' ) ) ) ) );

	$kit_id = (int) $elementor->kits_manager->get_active_id();
	if ( $kit_id ) {
		$kit      = $elementor->documents->get( $kit_id, false );
		$settings = (array) $kit->get_settings();
		$font     = function ( $id, $title, $family, $weight ) {
			return array( '_id' => $id, 'title' => $title, 'typography_typography' => 'custom', 'typography_font_family' => $family, 'typography_font_weight' => $weight );
		};
		$settings['system_colors'] = array(
			array( '_id' => 'primary', 'title' => 'Primary', 'color' => '#FFFFFF' ),
			array( '_id' => 'secondary', 'title' => 'Secondary', 'color' => '#F08BEA' ),
			array( '_id' => 'text', 'title' => 'Text', 'color' => '#F4F1EA' ),
			array( '_id' => 'accent', 'title' => 'Accent', 'color' => '#9444DC' ),
		);
		$settings['custom_colors'] = array(
			array( '_id' => 'dr_pink', 'title' => 'Gradient start', 'color' => '#E052D8' ),
			array( '_id' => 'dr_blue', 'title' => 'Gradient end', 'color' => '#3D4FE8' ),
			array( '_id' => 'dr_ink', 'title' => 'Background', 'color' => '#050507' ),
			array( '_id' => 'dr_panel', 'title' => 'Panel', 'color' => '#0B0A12' ),
		);
		$settings['system_typography'] = array(
			$font( 'primary', 'Primary', 'Audiowide', '400' ),
			$font( 'secondary', 'Secondary', 'Audiowide', '400' ),
			$font( 'text', 'Text', 'Archivo', '400' ),
			$font( 'accent', 'Accent', 'Archivo', '500' ),
		);
		$settings['container_width']        = array( 'unit' => 'px', 'size' => 1280, 'sizes' => array() );
		$settings['container_padding']      = array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true );
		$settings['body_background_background'] = 'classic';
		$settings['body_background_color']  = '#050507';
		$settings['body_color']             = '#F4F1EA';
		$settings['body_typography_typography']  = 'custom';
		$settings['body_typography_font_family'] = 'Archivo';
		$settings['link_normal_color']      = '#F08BEA';
		$settings['link_hover_color']       = '#A9B4FF';
		$kit->save( array( 'settings' => $settings ) );
		$log[] = "kit: $kit_id";
	}

	/* 7. Site identity. */
	update_option( 'blogname', 'Dance Reaction' );
	update_option( 'blogdescription', "Mobile Disco's · Melbourne" );

	$elementor->files_manager->clear_cache();
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
	$log[] = 'elementor ' . ELEMENTOR_VERSION . ', css cache cleared';
} catch ( Exception $e ) {
	$log[] = 'ERROR: ' . $e->getMessage();
}

return implode( "\n", $log );
