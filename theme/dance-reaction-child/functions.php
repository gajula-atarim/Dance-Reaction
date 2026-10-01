<?php
/**
 * Dance Reaction — Hello Elementor child theme.
 *
 * @package DanceReaction
 */

defined( 'ABSPATH' ) || exit;

define( 'DR_THEME_VERSION', '1.0.0' );
define( 'DR_THEME_DIR', get_stylesheet_directory() );
define( 'DR_THEME_URI', get_stylesheet_directory_uri() );

require_once DR_THEME_DIR . '/inc/booking-form.php';
require_once DR_THEME_DIR . '/inc/template-parts.php';

/**
 * Theme supports and menu locations.
 */
add_action( 'after_setup_theme', function () {
	register_nav_menus(
		array(
			'primary' => __( 'Header menu', 'dance-reaction' ),
			'footer'  => __( 'Footer menu', 'dance-reaction' ),
			'legal'   => __( 'Footer legal links', 'dance-reaction' ),
		)
	);
} );

/**
 * Styles and scripts.
 */
add_action( 'wp_enqueue_scripts', function () {
	$ver = DR_THEME_VERSION . '.' . (int) @filemtime( DR_THEME_DIR . '/assets/css/dr.css' );

	wp_enqueue_style( 'hello-elementor-parent', get_template_directory_uri() . '/style.css', array(), wp_get_theme( 'hello-elementor' )->get( 'Version' ) );
	wp_enqueue_style( 'dr-fonts', 'https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&family=Audiowide&display=swap', array(), null );
	wp_enqueue_style( 'dr-theme', DR_THEME_URI . '/assets/css/dr.css', array( 'hello-elementor-parent' ), $ver );

	$js_ver = DR_THEME_VERSION . '.' . (int) @filemtime( DR_THEME_DIR . '/assets/js/dr.js' );
	wp_enqueue_script( 'dr-theme', DR_THEME_URI . '/assets/js/dr.js', array(), $js_ver, true );
}, 20 );

/**
 * Same fonts and styles inside the Elementor editor preview.
 */
add_action( 'elementor/preview/enqueue_styles', function () {
	wp_enqueue_style( 'dr-fonts', 'https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&family=Audiowide&display=swap', array(), null );
} );

add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	echo '<meta name="theme-color" content="#050507">' . "\n";
}, 1 );

/**
 * Performance.
 */

// The theme already loads Archivo + Audiowide (only the weights used); stop Elementor
// from loading a second, much heavier copy of the same Google Fonts.
add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );

// New image sizes are generated as WebP (smaller than JPEG at the same quality).
// Originals are kept as uploaded.
add_filter( 'image_editor_output_format', function ( $formats ) {
	if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		$formats['image/jpeg'] = 'image/webp';
	}
	return $formats;
} );

// Preload the hero photo (a CSS background, so the browser would otherwise find it late).
add_action( 'wp_head', function () {
	if ( ! is_front_page() ) {
		return;
	}
	$hero = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_dr_source', 'meta_value' => 'dr-home-hero.jpg', 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $hero ) {
		// Same file the hero container uses as its background (the 1536px WebP copy when it exists).
		$url = wp_get_attachment_image_url( (int) $hero[0], '1536x1536' );
		if ( $url ) {
			printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( $url ) );
		}
	}
}, 2 );

// Content images are never wider than half the 1280px layout on desktop, so tell the
// browser that instead of "100vw" and it downloads a right-sized version.
add_filter( 'wp_calculate_image_sizes', function ( $sizes, $size ) {
	$width = is_array( $size ) ? (int) $size[0] : 0;
	if ( $width > 700 ) {
		return '(max-width: 767px) 100vw, (max-width: 1280px) 50vw, 640px';
	}
	return $sizes;
}, 10, 2 );

add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'dr-site';
	return $classes;
} );

/**
 * [dr_menu location="primary" class=""] — prints a theme menu location.
 * Used inside Elementor (Shortcode widget) so menus stay editable in Appearance → Menus.
 */
add_shortcode( 'dr_menu', function ( $atts ) {
	$atts = shortcode_atts(
		array(
			'location' => 'primary',
			'class'    => '',
		),
		$atts,
		'dr_menu'
	);

	if ( ! has_nav_menu( $atts['location'] ) ) {
		return '';
	}

	return wp_nav_menu(
		array(
			'theme_location' => $atts['location'],
			'container'      => 'nav',
			'container_class' => trim( 'dr-menu dr-menu--' . sanitize_html_class( $atts['location'] ) . ' ' . sanitize_html_class( $atts['class'] ) ),
			'menu_class'     => 'dr-menu__list',
			'depth'          => 1,
			'fallback_cb'    => false,
			'echo'           => false,
		)
	);
} );

/**
 * Menu links to a #section are not "the current page" — only real page links are.
 * (dr.js highlights the section in view on the home page instead.)
 */
add_filter( 'nav_menu_css_class', function ( $classes, $item ) {
	if ( 'custom' === $item->type && false !== strpos( (string) $item->url, '#' ) ) {
		$classes = array_diff( $classes, array( 'current-menu-item', 'current_page_item', 'current-menu-ancestor', 'current-menu-parent' ) );
	}
	return $classes;
}, 10, 2 );

add_filter( 'nav_menu_link_attributes', function ( $atts, $item ) {
	if ( in_array( 'current-menu-item', (array) $item->classes, true ) && false === strpos( (string) $item->url, '#' ) ) {
		$atts['aria-current'] = 'page';
	} else {
		unset( $atts['aria-current'] );
	}
	return $atts;
}, 10, 2 );

/**
 * [dr_year] — current year, for the footer copyright line.
 */
add_shortcode( 'dr_year', function () {
	return esc_html( wp_date( 'Y' ) );
} );
