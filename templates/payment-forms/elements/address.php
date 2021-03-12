<?php
/**
 * Displays an address in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/address.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $fields ) ) {
	return;
}

// A prefix for all ids (so that a form can be included in the same page multiple times).
$uniqid = uniqid( '_' );

// Prepare the user's country.
if ( ! empty( $form->invoice ) ) {
	$country = $form->invoice->get_country();
}

if ( empty( $country ) ) {
	$country = empty( $country ) ? getpaid_get_ip_country() : $country;
	$country = empty( $country ) ? wpinv_get_default_country() : $country;
}

// A prefix for all ids (so that a form can be included in the same page multiple times).
$uniqid = uniqid( '_' );

$address_type = empty( $address_type ) ? 'billing' : $address_type;

?>

<?php if ( 'both' === $address_type ) : ?>

	<!-- Start Billing/Shipping Address Title -->
	<h4 class="mb-3 getpaid-shipping-billing-address-title">
		<?php _e( 'Billing / Shipping Address', 'invoicing' ); ?>
	</h4>
	<!-- End Billing Address Title -->

	<!-- Start Billing Address Title -->
	<h4 class="mb-3 getpaid-billing-address-title">
		<?php _e( 'Billing Address', 'invoicing' ); ?>
	</h4>
	<!-- End Billing Address Title -->

<?php endif; ?>


<?php if ( 'both' === $address_type || 'billing' === $address_type ) : ?>

	<!-- Start Billing Address -->
	<div class="getpaid-billing-address-wrapper">
		<?php
			$field_type = 'billing';
			include plugin_dir_path( __FILE__ ) . 'address-fields.php';
			do_action( 'getpaid_after_payment_form_billing_fields', $form );
		?>
	</div>
	<!-- End Billing Address -->

<?php endif; ?>


<?php if ( 'both' === $address_type ) : ?>


	<?php

		echo aui()->input(
		    array(
			    'type'       => 'checkbox',
			    'name'       => 'same-shipping-address',
			    'id'         => "shipping-toggle$uniqid",
			    'required'   => false,
			    'label'      => wp_kses_post( $shipping_address_toggle ),
			    'value'      => 1,
			    'checked'    => true,
				'class'      => 'w-auto',
		    )
		);

	?>


	<!-- Start Shipping Address Title -->
	<h4 class="mb-3 getpaid-shipping-address-title">
		<?php _e( 'Shipping Address', 'invoicing' ); ?>
	</h4>
	<!-- End Shipping Address Title -->


<?php endif; ?>


	
<?php if ( 'both' === $address_type || 'shipping' === $address_type ) : ?>

	<!-- Start Shipping Address -->
	<div class="getpaid-shipping-address-wrapper">
		<?php
			$field_type = 'shipping';
			include plugin_dir_path( __FILE__ ) . 'address-fields.php';
			do_action( 'getpaid_after_payment_form_shipping_fields', $form );
		?>
	</div>
	<!-- End Shipping Address -->

<?php endif; ?>
