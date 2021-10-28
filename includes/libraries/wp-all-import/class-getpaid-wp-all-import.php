<?php
/**
 * Links GetPaid to WP All Import.
 *
 * @package GetPaid
 */

defined( 'ABSPATH' ) || exit;

include plugin_dir_path( __FILE__ ) . 'rapid-addon.php';

/**
 * WP All Import class.
 */
class GetPaid_WP_All_Import {

	/**
	 * @var RapidAddon
	 */
	protected $add_on;

    /**
	 * Class constructor.
	 */
    public function __construct() {

		// Register the add-on.
		$this->add_on = new RapidAddon( 'GetPaid', 'GetPaid_WP_All_Import_Addon' );
		$this->add_on->set_import_function( array( $this, 'import_items' ) );
		$this->add_item_fields();
		$this->add_on->run( array( 'post_types' => array( 'wpi_item' ) ) );

	}

	/**
	 * Retrieves item fields.
	 */
    public function get_item_fields() {

		return array(
			'description'          => __( 'Item Description', 'invoicing' ),
			'price'                => __( 'Price', 'invoicing' ),
			'vat_rule'             => __( 'VAT Rule', 'invoicing' ),
			'vat_class'            => __( 'VAT Class', 'invoicing' ),
			'type'                 => __( 'Trial Interval', 'invoicing' ),
			'custom_id'            => __( 'Custom ID', 'invoicing' ),
			'custom_name'          => __( 'Custom Name', 'invoicing' ),
			'custom_singular_name' => __( 'Custom Singular Name', 'invoicing' ),
			'is_dynamic_pricing'   => __( 'Users can name their own price', 'invoicing' ),
			'minimum_price'        => __( 'Minimum price', 'invoicing' ),
			'is_recurring'         => __( 'Is Recurring', 'invoicing' ),
			'recurring_period'     => __( 'Recurring Period', 'invoicing' ),
			'recurring_interval'   => __( 'Recurring Interval', 'invoicing' ),
			'recurring_limit'      => __( 'Recurring Limit', 'invoicing' ),
			'is_free_trial'        => __( 'Is free trial', 'invoicing' ),
			'trial_period'         => __( 'Trial Period', 'invoicing' ),
			'trial_interval'       => __( 'Trial Interval', 'invoicing' ),
		);

    }

	/**
	 * Registers item fields.
	 */
    public function add_item_fields() {

		foreach ( $this->get_item_fields() as $key => $label ) {
			$this->add_on->add_field( $key, $label, 'text' );
		}

    }

	/**
	 * Handles item imports.
	 */
    public function import_items( $post_id, $data, $import_options, $_post ) {

		$item     = wpinv_get_item( $post_id );
		$prepared = array();

		if ( empty( $item ) ) {
			return;
		}

		foreach ( array_keys( $this->get_item_fields() ) as $field ) { 
			// Make sure the user has allowed this field to be updated.
			if ( empty( $_post['ID'] ) || $this->add_on->can_update_meta( $field, $import_options ) ) { 
	
				// Update the custom field with the imported data.
				$prepared[ $field ] = $data[ $field ];
			} 
		}

		// Only update if we have something to update.
		if ( ! empty( $prepared ) ) {
			$item->set_props( $prepared );
			$item->save();
		}

    }

}

new GetPaid_WP_All_Import();
