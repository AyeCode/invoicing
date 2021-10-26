<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment form class
 *
 */
class GetPaid_Payment_Form extends GetPaid_Data {

    /**
	 * Which data store to load.
	 *
	 * @var string
	 */
    protected $data_store_name = 'payment_form';

    /**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'payment_form';

    /**
	 * Form Data array. This is the core form data exposed in APIs.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $data = array(
		'status'               => 'draft',
		'version'              => '',
		'date_created'         => null,
        'date_modified'        => null,
        'name'                 => '',
        'author'               => 1,
        'elements'             => null,
		'items'                => null,
		'earned'               => 0,
		'refunded'             => 0,
		'cancelled'            => 0,
		'failed'               => 0,
	);

    /**
	 * Stores meta in cache for future reads.
	 *
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'getpaid_forms';

	/**
	 * Stores a reference to the invoice if the form is for an invoice..
	 *
	 * @var WPInv_Invoice
	 */
	public $invoice = 0;

    /**
     * Stores a reference to the original WP_Post object
     *
     * @var WP_Post
     */
    protected $post = null;

    /**
	 * Get the form if ID is passed, otherwise the form is new and empty.
	 *
	 * @param  int|object|GetPaid_Payment_Form|WP_Post $form Form to read.
	 */
	public function __construct( $form = 0 ) {
		parent::__construct( $form );

		if ( is_numeric( $form ) && $form > 0 ) {
			$this->set_id( $form );
		} elseif ( $form instanceof self ) {

			$this->set_id( $form->get_id() );
			$this->invoice = $form->invoice;

		} elseif ( ! empty( $form->ID ) ) {
			$this->set_id( $form->ID );
		} else {
			$this->set_object_read( true );
		}

        // Load the datastore.
		$this->data_store = GetPaid_Data_Store::load( $this->data_store_name );

		if ( $this->get_id() > 0 ) {
            $this->post = get_post( $this->get_id() );
			$this->data_store->read( $this );
        }

	}

    /*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete items from the database.
	|
    */

    /*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
    */

    /**
	 * Get plugin version when the form was created.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_version( $context = 'view' ) {
		return $this->get_prop( 'version', $context );
    }

    /**
	 * Get date when the form was created.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
    }

    /**
	 * Get GMT date when the form was created.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_created_gmt( $context = 'view' ) {
        $date = $this->get_date_created( $context );

        if ( $date ) {
            $date = get_gmt_from_date( $date );
        }
		return $date;
    }

    /**
	 * Get date when the form was last modified.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
    }

    /**
	 * Get GMT date when the form was last modified.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_modified_gmt( $context = 'view' ) {
        $date = $this->get_date_modified( $context );

        if ( $date ) {
            $date = get_gmt_from_date( $date );
        }
		return $date;
    }

    /**
	 * Get the form name.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
    }

    /**
	 * Alias of self::get_name().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_title( $context = 'view' ) {
		return $this->get_name( $context );
	}

    /**
	 * Get the owner of the form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_author( $context = 'view' ) {
		return (int) $this->get_prop( 'author', $context );
    }

    /**
	 * Get the elements that make up the form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return array
	 */
	public function get_elements( $context = 'view' ) {
		$elements = $this->get_prop( 'elements', $context );

		if ( empty( $elements ) || ! is_array( $elements ) ) {
            return wpinv_get_data( 'sample-payment-form' );
		}

		// Ensure that all required elements exist.
		$_elements = array();
		foreach ( $elements as $element ) {

			if ( $element['type'] == 'pay_button' && ! $this->has_element_type( 'gateway_select' ) ) {

				$_elements[] = array(
					'text'        => __( 'Select Payment Method', 'invoicing' ),
					'id'          => 'gtscicd',
					'name'        => 'gtscicd',
					'type'        => 'gateway_select',
					'premade'     => true
			
				);

			}

			$_elements[] = $element;

		}

        return $_elements;
	}

	/**
	 * Get the items sold via the form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @param  string $return objects or arrays.
	 * @return GetPaid_Form_Item[]
	 */
	public function get_items( $context = 'view', $return = 'objects' ) {
		$items = $this->get_prop( 'items', $context );

		if ( empty( $items ) || ! is_array( $items ) ) {
            $items = wpinv_get_data( 'sample-payment-form-items' );
		}

		// Convert the items.
		$prepared = array();

		foreach ( $items as $key => $value ) {

			// Form items.
			if ( $value instanceof GetPaid_Form_Item ) {

				if ( $value->can_purchase() ) {
					$prepared[] = $value;
				}

				continue;

			}

			// $item_id => $quantity (buy buttons)
			if ( is_numeric( $key ) && is_numeric( $value ) ) {
				$item = new GetPaid_Form_Item( $key );

				if ( $item->can_purchase() ) {

					$value = (float) $value;
					$item->set_quantity( $value );
					if ( 0 == $value ) {
						$item->set_quantity( 1 );
						$item->set_allow_quantities( true );
					}

					$prepared[] = $item;
				}

				continue;
			}

			// Items saved via payment forms editor.
			if ( is_array( $value ) && isset( $value['id'] ) ) {

				$item = new GetPaid_Form_Item( $value['id'] );

				if ( ! $item->can_purchase() ) {
					continue;
				}

				// Sub-total (Cart items).
				if ( isset( $value['subtotal'] ) ) {
					$item->set_price( $value['subtotal'] );
				}

				if ( isset( $value['quantity'] ) ) {
					$item->set_quantity( $value['quantity'] );
				}

				if ( isset( $value['allow_quantities'] ) ) {
					$item->set_allow_quantities( $value['allow_quantities'] );
				}

				if ( isset( $value['required'] ) ) {
					$item->set_is_required( $value['required'] );
				}

				if ( isset( $value['description'] ) ) {
					$item->set_custom_description( $value['description'] );
				}

				$prepared[] = $item;
				continue;

			}

			// $item_id => array( 'price' => 10 ) (item variations)
			if ( is_numeric( $key ) && is_array( $value ) ) {
				$item = new GetPaid_Form_Item( $key );

				if ( isset( $value['price'] ) && $item->user_can_set_their_price() ) {
					$item->set_price( $value['price'] );
				}

				if ( $item->can_purchase() ) {
					$prepared[] = $item;
				}

				continue;
			}

		}

		if ( 'objects' == $return && 'view' == $context ) {
			return $prepared;
		}

		$items = array();
		foreach ( $prepared as $item ) {
			$items[] = $item->prepare_data_for_use();
		}

		return $items;
	}

	/**
	 * Get a single item belonging to the form.
	 *
	 * @since 1.0.19
	 * @param  int $item_id The item id to return.
	 * @return GetPaid_Form_Item|bool
	 */
	public function get_item( $item_id ) {

		if ( empty( $item_id ) || ! is_numeric( $item_id ) ) {
			return false;
		}

		foreach( $this->get_items() as $item ) {
			if ( $item->get_id() == (int) $item_id ) {
				return $item;
			}
		}

		return false;

	}

	/**
	 * Gets a single element.
	 *
	 * @since 1.0.19
	 * @param  string $element_type The element type to return.
	 * @return array|bool
	 */
	public function get_element_type( $element_type ) {

		if ( empty( $element_type ) || ! is_scalar( $element_type ) ) {
			return false;
		}

		foreach ( $this->get_prop( 'elements' ) as $element ) {

			if ( $element['type'] == $element_type ) {
				return $element;
			}

		}

		return false;

	}

	/**
	 * Get the total amount earned via this form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_earned( $context = 'view' ) {
		return $this->get_prop( 'earned', $context );
	}

	/**
	 * Get the total amount refunded via this form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_refunded( $context = 'view' ) {
		return $this->get_prop( 'refunded', $context );
	}

	/**
	 * Get the total amount cancelled via this form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_cancelled( $context = 'view' ) {
		return $this->get_prop( 'cancelled', $context );
	}

	/**
	 * Get the total amount failed via this form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_failed( $context = 'view' ) {
		return $this->get_prop( 'failed', $context );
	}

	/**
	 * Get the currency.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_currency() {
		$currency = empty( $this->invoice ) ? wpinv_get_currency() : $this->invoice->get_currency();
		return apply_filters( 'getpaid-payment-form-currency', $currency, $this );
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
	 * Set plugin version when the item was created.
	 *
	 * @since 1.0.19
	 */
	public function set_version( $value ) {
		$this->set_prop( 'version', $value );
    }

    /**
	 * Set date when the item was created.
	 *
	 * @since 1.0.19
	 * @param string $value Value to set.
     * @return bool Whether or not the date was set.
	 */
	public function set_date_created( $value ) {
        $date = strtotime( $value );

        if ( $date ) {
            $this->set_prop( 'date_created', date( 'Y-m-d H:i:s', $date ) );
            return true;
        }

        return false;

    }

    /**
	 * Set date when the item was last modified.
	 *
	 * @since 1.0.19
	 * @param string $value Value to set.
     * @return bool Whether or not the date was set.
	 */
	public function set_date_modified( $value ) {
        $date = strtotime( $value );

        if ( $date ) {
            $this->set_prop( 'date_modified', date( 'Y-m-d H:i:s', $date ) );
            return true;
        }

        return false;

    }

    /**
	 * Set the item name.
	 *
	 * @since 1.0.19
	 * @param  string $value New name.
	 */
	public function set_name( $value ) {
		$this->set_prop( 'name', sanitize_text_field( $value ) );
    }

    /**
	 * Alias of self::set_name().
	 *
	 * @since 1.0.19
	 * @param  string $value New name.
	 */
	public function set_title( $value ) {
		$this->set_name( $value );
    }

    /**
	 * Set the owner of the item.
	 *
	 * @since 1.0.19
	 * @param  int $value New author.
	 */
	public function set_author( $value ) {
		$this->set_prop( 'author', (int) $value );
	}

	/**
	 * Set the form elements.
	 *
	 * @since 1.0.19
	 * @sinve 2.3.4 Array values sanitized.
	 * @param  array $value Form elements.
	 */
	public function set_elements( $value ) {
		if ( is_array( $value ) ) {
			$this->set_prop( 'elements', wp_kses_post_deep( $value ) );
		}
	}

	/**
	 * Sanitize array values.
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	public function sanitize_array_values($value){

		// sanitize
		if(!empty($value )){

			foreach($value as $key => $val_arr){

				if(is_array($val_arr)){
					// check if we have sub array items.
					$sub_arr = array();
					foreach($val_arr as $key2 => $val2){
						if(is_array($val2)){
							$sub_arr[$key2] = $this->sanitize_array_values($val2);
							unset($val_arr[$key][$key2]);
						}
					}

					// we allow some html in description so we sanitize it separately.
					$help_text = !empty($val_arr['description']) ? wp_kses_post($val_arr['description']) : '';

					// sanitize array elements
					$value[$key] = array_map( 'sanitize_text_field', $val_arr );

					// add back the description if set
					if(isset($val_arr['description'])){ $value[$key]['description'] = $help_text;}

					// add back sub array items after its been sanitized.
					if ( ! empty( $sub_arr ) ) {
						$value[$key] = array_merge($value[$key],$sub_arr);
					}
				}

			}

		}

		return $value;
	}

	/**
	 * Set the form items.
	 *
	 * @since 1.0.19
	 * @param  array $value Form elements.
	 */
	public function set_items( $value ) {
		if ( is_array( $value ) ) {
			$this->set_prop( 'items', $value );
		}
	}

	/**
	 * Set the total amount earned via this form.
	 *
	 * @since 1.0.19
	 * @param  float $value Amount earned.
	 */
	public function set_earned( $value ) {
		$value = max( (float) $value, 0 );
		$this->set_prop( 'earned', $value );
	}

	/**
	 * Set the total amount refunded via this form.
	 *
	 * @since 1.0.19
	 * @param  float $value Amount refunded.
	 */
	public function set_refunded( $value ) {
		$value = max( (float) $value, 0 );
		$this->set_prop( 'refunded', $value );
	}

	/**
	 * Set the total amount cancelled via this form.
	 *
	 * @since 1.0.19
	 * @param  float $value Amount cancelled.
	 */
	public function set_cancelled( $value ) {
		$value = max( (float) $value, 0 );
		$this->set_prop( 'cancelled', $value );
	}

	/**
	 * Set the total amount failed via this form.
	 *
	 * @since 1.0.19
	 * @param  float $value Amount cancelled.
	 */
	public function set_failed( $value ) {
		$value = max( (float) $value, 0 );
		$this->set_prop( 'failed', $value );
	}

    /**
     * Create an item. For backwards compatibilty.
     *
     * @deprecated
	 * @return int item id
     */
    public function create( $data = array() ) {

		// Set the properties.
		if ( is_array( $data ) ) {
			$this->set_props( $data );
		}

		// Save the item.
		return $this->save();

    }

    /**
     * Updates an item. For backwards compatibilty.
     *
     * @deprecated
	 * @return int item id
     */
    public function update( $data = array() ) {
        return $this->create( $data );
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
	 * Checks whether this is the default payment form.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
    public function is_default() {
        $is_default = $this->get_id() == wpinv_get_default_payment_form();
        return (bool) apply_filters( 'wpinv_is_default_payment_form', $is_default, $this->get_id(), $this );
	}

    /**
	 * Checks whether the form is active.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
    public function is_active() {
        $is_active = 0 !== (int) $this->get_id();

        if ( $is_active && ! current_user_can( 'edit_post', $this->get_id() ) && $this->get_status() != 'publish' ) {
            $is_active = false;
        }

        return (bool) apply_filters( 'wpinv_is_payment_form_active', $is_active, $this );
	}

	/**
	 * Checks whether the form has a given item.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
    public function has_item( $item_id ) {
        return false !== $this->get_item( $item_id );
	}

	/**
	 * Checks whether the form has a given element.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
    public function has_element_type( $element_type ) {
        return false !== $this->get_element_type( $element_type );
	}

	/**
	 * Checks whether this form is recurring or not.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
    public function is_recurring() {

		if ( ! empty( $this->invoice ) ) {
			return $this->invoice->is_recurring();
		}

		foreach ( $this->get_items() as $item ) {

			if ( $item->is_recurring() ) {
				return true;
			}

		}

        return false;
	}

	/**
	 * Retrieves the form's html.
	 *
	 * @since 1.0.19
	 */
    public function get_html( $extra_markup = '' ) {

		// Return the HTML.
		return wpinv_get_template_html(
			'payment-forms/form.php',
			array(
				'form'         => $this,
				'extra_markup' => $extra_markup,
			)
		);

	}

	/**
	 * Displays the payment form.
	 *
	 * @since 1.0.19
	 */
    public function display( $extra_markup = '' ) {
		echo $this->get_html( $extra_markup );
    }

}
