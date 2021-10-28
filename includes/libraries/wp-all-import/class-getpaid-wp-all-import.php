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
	 * @var RapidAddon[]
	 */
	protected $add_ons;

	/**
	 * @var array
	 */
	protected $datastores = array(
		'item'     =>'WPInv_Item',
		'invoice'  =>'WPInv_Invoice',
		'discount' =>'WPInv_Discount',
	);

    /**
	 * Class constructor.
	 */
    public function __construct() {

		// Init each store separately.
		foreach ( array_keys( $this->datastores ) as $key ) {
			$this->init_store( $key );
		}

	}

	/**
	 * Inits a store.
	 */
    public function init_store( $key ) {

		// Register the add-on.
		$this->add_ons[ $key ] = new RapidAddon( 'GetPaid', 'getpaid_wp_al_import_' . $key );

		// Create import function.
		$import_function = function ( $post_id, $data, $import_options, $_post ) use ( $key ) {
			$this->import_store( $key, $post_id, $data, $import_options, $_post );
        };

		$this->add_ons[ $key ]->set_import_function( $import_function );

		// Register store fields.
		$this->add_store_fields( $key );

		// Only load on the correct post type.
		$this->add_ons[ $key ]->run( array( 'post_types' => array( 'wpi_' . $key ) ) );

		// Disable images.
		$this->add_ons[ $key ]->disable_default_images();

	}

	/**
	 * Retrieves store fields.
	 */
    public function get_store_fields( $key ) {

		// Fetch from data/invoice-schema.php, from data/discount-schema.php, from data/item-schema.php
		$fields = wpinv_get_data( $key . '-schema' );

		if ( empty( $fields ) ) {
			return array();
		}

		// Clean the fields.
		$prepared = array();
		foreach ( $fields as $id => $field ) {

			// Skip read only fields.
			if ( ! empty( $field['readonly'] ) ) {
				continue;
			}

			$prepared[ $id ] = $field;

		}

		return $prepared;

	}

	/**
	 * Registers store fields.
	 */
    public function add_store_fields( $key ) {

		foreach ( $this->get_store_fields( $key ) as $field_id => $data ) {
			$this->add_ons[ $key ]->add_field( $field_id, $data['description'], 'text' );
		}

    }

	/**
	 * Handles store imports.
	 */
    public function import_store( $key, $post_id, $data, $import_options, $_post ) {

		// Is the store class set?
		if ( ! isset( $this->datastores[ $key ] ) ) {
			return;
		}

		/**@var GetPaid_Data */
		$data_store = new $this->datastores[ $key ]( $post_id );

		// Abort if the invoice/item/discount does not exist.
		if ( ! $data_store->exists() ) {
			return;
		}

		// Prepare data props.
		$prepared = array();

		foreach ( array_keys( $this->get_store_fields( $key ) ) as $field ) { 
			// Make sure the user has allowed this field to be updated.
			if ( empty( $_post['ID'] ) || $this->add_ons[ $key ]->can_update_meta( $field, $import_options ) ) { 
	
				// Update the custom field with the imported data.
				$prepared[ $field ] = $data[ $field ];
			} 
		}

		// Only update if we have something to update.
		if ( ! empty( $prepared ) ) {
			$data_store->set_props( $prepared );
			$data_store->save();
		}

    }

}

new GetPaid_WP_All_Import();
