<?php
/**
 * Booking enquiry form — [dr_booking_form].
 *
 * Elementor's free version has no Form widget, so the form is a shortcode that is
 * placed with Elementor's Shortcode widget. Submissions are emailed to the address in
 * the `to` attribute (default: Settings → General → Administration Email address).
 *
 * Attributes:
 *   to      Recipient email.
 *   button  Submit button label.
 *   types   Comma-separated event types for the dropdown.
 *
 * @package DanceReaction
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'dr_booking_form', function ( $atts ) {
	$atts = shortcode_atts(
		array(
			'to'     => '',
			'button' => __( 'Send booking enquiry', 'dance-reaction' ),
			'types'  => 'Wedding, Corporate dinner, Birthday, Other event',
		),
		$atts,
		'dr_booking_form'
	);

	$types  = array_filter( array_map( 'trim', explode( ',', $atts['types'] ) ) );
	$status = isset( $_GET['enquiry'] ) ? sanitize_key( wp_unslash( $_GET['enquiry'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

	ob_start();
	?>
	<form class="dr-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php if ( 'sent' === $status ) : ?>
			<p class="dr-form__notice dr-form__notice--ok" role="status"><?php esc_html_e( 'Thanks! Your enquiry has been sent. I will be in touch shortly.', 'dance-reaction' ); ?></p>
		<?php elseif ( 'error' === $status ) : ?>
			<p class="dr-form__notice dr-form__notice--err" role="alert"><?php esc_html_e( 'Sorry, something went wrong. Please check your details or call directly.', 'dance-reaction' ); ?></p>
		<?php endif; ?>

		<input type="hidden" name="action" value="dr_booking_enquiry">
		<input type="hidden" name="dr_to" value="<?php echo esc_attr( $atts['to'] ? wp_hash( $atts['to'] ) . '|' . $atts['to'] : '' ); ?>">
		<?php wp_nonce_field( 'dr_booking_enquiry', 'dr_nonce' ); ?>
		<input type="hidden" name="dr_redirect" value="<?php echo esc_url( get_permalink() ?: home_url( '/' ) ); ?>">
		<div class="dr-form__hp" aria-hidden="true"><label>Website<input type="text" name="dr_website" tabindex="-1" autocomplete="off"></label></div>

		<label class="dr-form__field"><span><?php esc_html_e( 'Name', 'dance-reaction' ); ?></span><input type="text" name="dr_name" autocomplete="name" required></label>
		<label class="dr-form__field"><span><?php esc_html_e( 'Phone', 'dance-reaction' ); ?></span><input type="tel" name="dr_phone" autocomplete="tel"></label>
		<label class="dr-form__field dr-form__field--full"><span><?php esc_html_e( 'Email', 'dance-reaction' ); ?></span><input type="email" name="dr_email" autocomplete="email" required></label>
		<label class="dr-form__field"><span><?php esc_html_e( 'Event type', 'dance-reaction' ); ?></span>
			<select name="dr_type">
				<?php foreach ( $types as $type ) : ?>
					<option><?php echo esc_html( $type ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="dr-form__field"><span><?php esc_html_e( 'Event date', 'dance-reaction' ); ?></span><input type="date" name="dr_date"></label>
		<label class="dr-form__field dr-form__field--full"><span><?php esc_html_e( 'Message', 'dance-reaction' ); ?></span><textarea name="dr_message" rows="3"></textarea></label>
		<button type="submit" class="dr-form__submit"><?php echo esc_html( $atts['button'] ); ?></button>
	</form>
	<?php
	return ob_get_clean();
} );

/**
 * Handle submissions (logged-in and logged-out visitors).
 */
function dr_handle_booking_enquiry() {
	$redirect = isset( $_POST['dr_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['dr_redirect'] ) ) : home_url( '/' );
	$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );

	$fail = function () use ( $redirect ) {
		wp_safe_redirect( add_query_arg( 'enquiry', 'error', $redirect ) . '#enquiry' );
		exit;
	};

	if ( ! isset( $_POST['dr_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dr_nonce'] ) ), 'dr_booking_enquiry' ) ) {
		$fail();
	}

	// Honeypot: bots fill every field.
	if ( ! empty( $_POST['dr_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'enquiry', 'sent', $redirect ) . '#enquiry' );
		exit;
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['dr_name'] ?? '' ) );
	$phone   = sanitize_text_field( wp_unslash( $_POST['dr_phone'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['dr_email'] ?? '' ) );
	$type    = sanitize_text_field( wp_unslash( $_POST['dr_type'] ?? '' ) );
	$date    = sanitize_text_field( wp_unslash( $_POST['dr_date'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['dr_message'] ?? '' ) );

	if ( '' === $name || ! is_email( $email ) ) {
		$fail();
	}

	// Recipient: a signed `to` attribute from the shortcode, otherwise the admin email.
	$to = get_option( 'admin_email' );
	if ( ! empty( $_POST['dr_to'] ) ) {
		$parts = explode( '|', sanitize_text_field( wp_unslash( $_POST['dr_to'] ) ), 2 );
		if ( 2 === count( $parts ) && hash_equals( wp_hash( $parts[1] ), $parts[0] ) && is_email( $parts[1] ) ) {
			$to = $parts[1];
		}
	}

	$subject = sprintf( '[%s] Booking enquiry: %s%s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $type, $date ? ' — ' . $date : '' );
	$body    = implode(
		"\n",
		array(
			'Name: ' . $name,
			'Phone: ' . $phone,
			'Email: ' . $email,
			'Event type: ' . $type,
			'Event date: ' . $date,
			'',
			'Message:',
			$message,
		)
	);
	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

	$sent = wp_mail( $to, $subject, $body, $headers );

	/**
	 * Fires after a booking enquiry is processed.
	 *
	 * @param array $data Submitted values.
	 * @param bool  $sent Whether wp_mail() succeeded.
	 */
	do_action( 'dr_booking_enquiry_submitted', compact( 'name', 'phone', 'email', 'type', 'date', 'message' ), $sent );

	wp_safe_redirect( add_query_arg( 'enquiry', $sent ? 'sent' : 'error', $redirect ) . '#enquiry' );
	exit;
}
add_action( 'admin_post_dr_booking_enquiry', 'dr_handle_booking_enquiry' );
add_action( 'admin_post_nopriv_dr_booking_enquiry', 'dr_handle_booking_enquiry' );
