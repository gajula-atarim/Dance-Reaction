<?php
/**
 * Site header / footer.
 *
 * The header and footer are built with Ultimate Addons for Elementor
 * (Appearance → UAE → Header/Footer Builder → "Site Header (UAE)" / "Site Footer (UAE)").
 * If UAE is ever switched off, a minimal fallback bar is shown so pages never break.
 *
 * @package DanceReaction
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site header. Defers to an Elementor Pro theme-builder header when one exists.
 */
function dr_site_header() {
	if ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'header' ) ) {
		return;
	}

	if ( function_exists( 'hfe_header_enabled' ) && hfe_header_enabled() ) {
		echo '<header id="site-header" class="dr-site-header dr-site-header--hfe">';
		hfe_render_header();
		echo '</header>';
		return;
	}

	printf(
		'<header id="site-header" class="dr-site-header"><div class="dr-fallback-bar"><a class="dr-logo-text" href="%1$s">%2$s</a></div></header>',
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

/**
 * Site footer. Defers to an Elementor Pro theme-builder footer when one exists.
 */
function dr_site_footer() {
	if ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'footer' ) ) {
		return;
	}

	if ( function_exists( 'hfe_footer_enabled' ) && hfe_footer_enabled() ) {
		echo '<footer id="site-footer" class="dr-site-footer dr-site-footer--hfe">';
		hfe_render_footer();
		echo '</footer>';
		return;
	}

	printf(
		'<footer id="site-footer" class="dr-site-footer"><div class="dr-fallback-bar">&copy; %1$s %2$s</div></footer>',
		esc_html( wp_date( 'Y' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}
