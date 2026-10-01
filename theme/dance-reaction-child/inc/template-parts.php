<?php
/**
 * Site header / footer rendered from Elementor templates.
 *
 * The header and footer are regular Elementor templates (Templates → Saved Templates),
 * so they are edited with Elementor like any page. Their IDs are stored in the
 * `dr_header_template_id` / `dr_footer_template_id` options and can be changed in
 * Appearance → Customize → Dance Reaction layout.
 *
 * @package DanceReaction
 */

defined( 'ABSPATH' ) || exit;

/**
 * Output an Elementor template by ID. Returns false when nothing was printed.
 */
function dr_render_elementor_template( $template_id ) {
	$template_id = absint( $template_id );

	if ( ! $template_id || ! did_action( 'elementor/loaded' ) || 'publish' !== get_post_status( $template_id ) ) {
		return false;
	}

	// Don't nest the header/footer inside itself while it is being edited.
	if ( is_singular( 'elementor_library' ) && get_queried_object_id() === $template_id ) {
		return false;
	}

	$html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id, true );

	if ( '' === trim( (string) $html ) ) {
		return false;
	}

	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- Elementor output.
	return true;
}

/**
 * Site header. Defers to an Elementor Pro theme-builder header when one exists.
 */
function dr_site_header() {
	if ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'header' ) ) {
		return;
	}

	echo '<header id="site-header" class="dr-site-header">';
	if ( ! dr_render_elementor_template( get_option( 'dr_header_template_id' ) ) ) {
		printf(
			'<div class="dr-fallback-bar"><a class="dr-logo-text" href="%1$s">%2$s</a></div>',
			esc_url( home_url( '/' ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
	}
	echo '</header>';
}

/**
 * Site footer. Defers to an Elementor Pro theme-builder footer when one exists.
 */
function dr_site_footer() {
	if ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'footer' ) ) {
		return;
	}

	echo '<footer id="site-footer" class="dr-site-footer">';
	if ( ! dr_render_elementor_template( get_option( 'dr_footer_template_id' ) ) ) {
		printf(
			'<div class="dr-fallback-bar">&copy; %1$s %2$s</div>',
			esc_html( wp_date( 'Y' ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
	}
	echo '</footer>';
}

/**
 * Customizer: choose which Elementor templates act as header and footer.
 */
add_action( 'customize_register', function ( $wp_customize ) {
	$choices = array( 0 => __( '— None —', 'dance-reaction' ) );
	$templates = get_posts(
		array(
			'post_type'      => 'elementor_library',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	foreach ( $templates as $template ) {
		$choices[ $template->ID ] = $template->post_title;
	}

	$wp_customize->add_section(
		'dr_layout',
		array(
			'title'    => __( 'Dance Reaction layout', 'dance-reaction' ),
			'priority' => 30,
		)
	);

	foreach ( array(
		'dr_header_template_id' => __( 'Header template (Elementor)', 'dance-reaction' ),
		'dr_footer_template_id' => __( 'Footer template (Elementor)', 'dance-reaction' ),
	) as $option => $label ) {
		$wp_customize->add_setting(
			$option,
			array(
				'type'              => 'option',
				'sanitize_callback' => 'absint',
			)
		);
		$wp_customize->add_control(
			$option,
			array(
				'label'   => $label,
				'section' => 'dr_layout',
				'type'    => 'select',
				'choices' => $choices,
			)
		);
	}
} );
