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
 * [dr_year] — current year, for the footer copyright line.
 */
add_shortcode( 'dr_year', function () {
	return esc_html( wp_date( 'Y' ) );
} );
