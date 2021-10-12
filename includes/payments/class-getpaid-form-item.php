<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form Item Class
 *
 */
class GetPaid_Form_Item  extends WPInv_Item {

    /**
	 * Stores a custom description for the item.
	 *
	 * @var string
	 */
	protected $custom_description = null;

	/**
	 * Stores the item quantity.
	 *
	 * @var float
	 */
	protected $quantity = 1;

	/**
	 * Stores the item meta.
	 *
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Is this item required?
	 *
	 * @var int
	 */
	protected $is_required = true;

	/**
	 * Are quantities allowed?
	 *
	 * @var int
	 */
	protected $allow_quantities = false;

	/**
	 * Associated invoice.
	 *
	 * @var int
	 */
	public $invoice_id = 0;

	/**
	 * Item discount.
	 *
	 * @var float
	 */
	public $item_discount = 0;

	/**
	 * Recurring item discount.
	 *
	 * @var float
	 */
	public $recurring_item_discount = 0;

	/**
	 * Item tax.
	 *
	 * @var float
	 */
	public $item_tax = 0;

    /*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete items from the object.
	|
    */

    /*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
    */

    /**
	 * Get the item name.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		$name = parent::get_name( $context );
		return $name . wpinv_get_item_suffix( $this );
	}

	/**
	 * Get the item name without a suffix.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_raw_name( $context = 'view' ) {
		return parent::get_name( $context );
	}

	/**
	 * Get the item description.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_description( $context = 'view' ) {

		if ( isset( $this->custom_description ) ) {
			return $this->custom_description;
		}

		return parent::get_description( $context );
	}

	/**
	 * Returns the sub total.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_sub_total( $context = 'view' ) {
		return $this->get_quantity( $context ) * $this->get_initial_price( $context );
	}

	/**
	 * Returns the recurring sub total.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_recurring_sub_total( $context = 'view' ) {

		if ( $this->is_recurring() ) {
			return $this->get_quantity( $context ) * $this->get_price( $context );
		}

		return 0;
	}

	/**
	 * @deprecated
	 */
	public function get_qantity( $context = 'view' ) {
		return $this->get_quantity( $context );
	}

	/**
	 * Get the item quantity.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_quantity( $context = 'view' ) {
		$quantity = (float) $this->quantity;

		if ( 'view' == $context ) {
			return apply_filters( 'getpaid_payment_form_item_quantity', $quantity, $this );
		}

		return $quantity;

	}

	/**
	 * Get the item meta data.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return meta
	 */
	public function get_item_meta( $context = 'view' ) {
		$meta = $this->meta;

		if ( 'view' == $context ) {
			return apply_filters( 'getpaid_payment_form_item_meta', $meta, $this );
		}

		return $meta;

	}

	/**
	 * Returns whether or not customers can update the item quantity.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return bool
	 */
	public function get_allow_quantities( $context = 'view' ) {
		$allow_quantities = (bool) $this->allow_quantities;

		if ( 'view' == $context ) {
			return apply_filters( 'getpaid_payment_form_item_allow_quantities', $allow_quantities, $this );
		}

		return $allow_quantities;

	}

	/**
	 * Returns whether or not the item is required.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return bool
	 */
	public function get_is_required( $context = 'view' ) {
		$is_required = (bool) $this->is_required;

		if ( 'view' == $context ) {
			return apply_filters( 'getpaid_payment_form_item_is_required', $is_required, $this );
		}

		return $is_required;

	}

	/**
	 * Prepares form data for use.
	 *
	 * @since 1.0.19
	 * @return array
	 */
	public function prepare_data_for_use( $required = null ) {

		$required = is_null( $required ) ? $this->is_required() : $required;
		return array(
			'title'            => strip_tags( $this->get_name() ),
			'id'               => $this->get_id(),
			'price'            => $this->get_price(),
			'recurring'        => $this->is_recurring(),
			'description'      => $this->get_description(),
			'allow_quantities' => $this->allows_quantities(),
			'required'         => $required,
		);

	}

	/**
	 * Prepares form data for ajax use.
	 *
	 * @since 1.0.19
	 * @return array
	 */
	public function prepare_data_for_invoice_edit_ajax( $currency = '', $is_renewal = false ) {

		$description = getpaid_item_recurring_price_help_text( $this, $currency );

		if ( $description ) {
			$description = "<div class='getpaid-subscription-help-text'>$description</div>";
		}

		$price    = ! $is_renewal ? $this->get_price() : $this->get_recurring_price();
		$subtotal = ! $is_renewal ? $this->get_sub_total() : $this->get_recurring_sub_total();
		return array(
			'id'     => $this->get_id(),
			'texts'  => array(
				'item-name'        => sanitize_text_field( $this->get_name() ),
				'item-description' => wp_kses_post( $this->get_description() ) . $description,
				'item-quantity'    => floatval( $this->get_quantity() ),
				'item-price'       => wpinv_price( $price, $currency ),
				'item-total'       => wpinv_price( $subtotal, $currency ),
			),
			'inputs' => array(
				'item-id'          => $this->get_id(),
				'item-name'        => sanitize_text_field( $this->get_name() ),
				'item-description' => wp_kses_post( $this->get_description() ),
				'item-quantity'    => floatval( $this->get_quantity() ),
				'item-price'       => $price,
			)
		);

	}

	/**
	 * Prepares form data for saving (cart_details).
	 *
	 * @since 1.0.19
	 * @return array
	 */
	public function prepare_data_for_saving() {

		return array(
			'post_id'           => $this->invoice_id,
			'item_id'           => $this->get_id(),
			'item_name'         => sanitize_text_field( $this->get_raw_name( 'edit' ) ),
			'item_description'  => $this->get_description( 'edit' ),
			'tax'               => $this->item_tax,
			'item_price'        => $this->get_price( 'edit' ),
			'quantity'          => (float) $this->get_quantity( 'edit' ),
			'discount'          => $this->item_discount,
			'subtotal'          => $this->get_sub_total( 'edit' ),
			'price'             => $this->get_sub_total( 'edit' ) + $this->item_tax - $this->item_discount,
			'meta'              => $this->get_item_meta( 'edit' ),
		);

	}

    /*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting order data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
    */

	/**
	 * Set the item qantity.
	 *
	 * @since 1.0.19
	 * @param  float $quantity The item quantity.
	 */
	public function set_quantity( $quantity ) {

		if ( ! is_numeric( $quantity ) ) {
			$quantity = 1;
		}

		$this->quantity = (float) $quantity;

	}

	/**
	 * Set the item meta data.
	 *
	 * @since 1.0.19
	 * @param  array $meta The item meta data.
	 */
	public function set_item_meta( $meta ) {
		$this->meta = maybe_unserialize( $meta );
	}

	/**
	 * Set whether or not the quantities are allowed.
	 *
	 * @since 1.0.19
	 * @param  bool $allow_quantities
	 */
	public function set_allow_quantities( $allow_quantities ) {
		$this->allow_quantities = (bool) $allow_quantities;
	}

	/**
	 * Set whether or not the item is required.
	 *
	 * @since 1.0.19
	 * @param  bool $is_required
	 */
	public function set_is_required( $is_required ) {
		$this->is_required = (bool) $is_required;
	}

	/**
	 * Sets the custom item description.
	 *
	 * @since 1.0.19
	 * @param  string $description
	 */
	public function set_custom_description( $description ) {
		$this->custom_description = $description;
	}

    /**
     * We do not want to save items to the database.
     * 
	 * @return int item id
     */
    public function save( $data = array() ) {
        return $this->get_id();
	}

    /*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	|
	| Checks if a condition is true or false.
	|
	*/

    /**
	 * Checks whether the item has enabled dynamic pricing.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function is_required() {
        return (bool) $this->get_is_required();
	}

	/**
	 * Checks whether users can edit the quantities.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function allows_quantities() {
        return (bool) $this->get_allow_quantities();
	}

}
