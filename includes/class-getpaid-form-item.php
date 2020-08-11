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
	protected $custom_description = '';

	/**
	 * Stores the item quantity.
	 *
	 * @var int
	 */
	protected $quantity = 1;

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
	 * Get the item description.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_description( $context = 'view' ) {

		if ( ! empty( $this->custom_description ) ) {
			return $this->custom_description;
		}

		return parent::get_description( $context );
	}
	
	/**
	 * Returns the sub total.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_sub_total( $context = 'view' ) {
		return $this->get_quantity( $context ) * $this->get_price( $context );
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
	 * @return int
	 */
	public function get_quantity( $context = 'view' ) {
		$quantity = (int) $this->quantity;

		if ( empty( $quantity ) || 1 > $quantity ) {
			$quantity = 1;
		}

		if ( 'view' == $context ) {
			return apply_filters( 'getpaid_payment_form_item_quanity', $quantity, $this );
		}

		return $quantity;

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
	 * @param  string $context View or edit context.
	 * @return array
	 */
	public function prepare_data_for_use() {

		return array(
			'title'            => sanitize_text_field( $this->get_name() ),
			'id'               => $this->get_id(),
			'price'            => $this->get_price(),
			'recurring'        => $this->is_recurring(),
			'description'      => $this->get_description(),
			'allow_quantities' => $this->allows_quantities(),
			'required'         => $this->is_required(),
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
	 * @param  int $quantity The item quantity.
	 */
	public function set_quantity( $quantity ) {

		if ( empty( $quantity ) || ! is_numeric( $quantity ) ) {
			$quantity = 1;
		}

		$this->quantity = $quantity;

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
