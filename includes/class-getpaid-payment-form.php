<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment form class
 *
 */
class GetPaid_Payment_Form  extends GetPaid_Data {

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
     * Stores a reference to the original WP_Post object
     *
     * @var WP_Post
     */
    protected $post = null;

    /**
	 * Get the item if ID is passed, otherwise the item is new and empty.
	 *
	 * @param  int|object|WPInv_Item|WP_Post $item Item to read.
	 */
	public function __construct( $item = 0 ) {
		parent::__construct( $item );

		if ( is_numeric( $item ) && $item > 0 ) {
			$this->set_id( $item );
		} elseif ( $item instanceof self ) {
			$this->set_id( $item->get_id() );
		} elseif ( ! empty( $item->ID ) ) {
			$this->set_id( $item->ID );
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
        return $elements;
	}

	/**
	 * Get the items sold via the form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return GetPaid_Form_Item[]
	 */
	public function get_items( $context = 'view' ) {
		$items = $this->get_prop( 'items', $context );

		if ( empty( $items ) || ! is_array( $items ) ) {
            $items = wpinv_get_data( 'sample-payment-form-items' );
        }

		// Convert the items.
		$prepared = array();

		foreach ( $items as $key => $value ) {

			// $item_id => $quantity
			if ( is_numeric( $key ) && is_numeric( $value ) ) {
				$item   = new GetPaid_Form_Item( $key );

				if ( $item->can_purchase() ) {
					$item->set_quantity( $value );
					$prepared[] = $item;
				}

				continue;
			}

			if ( is_array( $value ) && isset( $value['id'] ) ) {

				$item = new GetPaid_Form_Item( $value['id'] );

				if ( ! $item->can_purchase() ) {
					continue;
				}

				// Cart items.
				if ( isset( $value['subtotal'] ) ) {
					$item->set_price( $value['subtotal'] );
					$item->set_quantity( $value['quantity'] );
					$prepared[] = $item;
					continue;
				}

				// Payment form item.
				$item->set_quantity( $value['quantity'] );
				$item->set_allow_quantities( $value['allow_quantities'] );
				$item->set_is_required( $value['required'] );
				$item->set_custom_description( $value['description'] );
				$prepared[] = $item;
				continue;

			}
		}

		return $prepared;
	}

	/**
	 * Get the total amount earned via this form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return array
	 */
	public function get_earned( $context = 'view' ) {
		return $this->get_prop( 'earned', $context );
	}

	/**
	 * Get the total amount refunded via this form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return array
	 */
	public function get_refunded( $context = 'view' ) {
		return $this->get_prop( 'refunded', $context );
	}

	/**
	 * Get the total amount cancelled via this form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return array
	 */
	public function get_cancelled( $context = 'view' ) {
		return $this->get_prop( 'cancelled', $context );
	}

	/**
	 * Get the total amount failed via this form.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return array
	 */
	public function get_failed( $context = 'view' ) {
		return $this->get_prop( 'failed', $context );
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
	 * @param  array $value Form elements.
	 */
	public function set_elements( $value ) {
		if ( is_array( $value ) ) {
			$this->set_prop( 'elements', $value );
		}
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
	 * @return array
	 */
	public function set_earned( $value ) {
		return $this->set_prop( 'earned', $value );
	}

	/**
	 * Set the total amount refunded via this form.
	 *
	 * @since 1.0.19
	 * @param  float $value Amount refunded.
	 * @return array
	 */
	public function set_refunded( $value ) {
		return $this->set_prop( 'refunded', $value );
	}

	/**
	 * Set the total amount cancelled via this form.
	 *
	 * @since 1.0.19
	 * @param  float $value Amount cancelled.
	 * @return array
	 */
	public function set_cancelled( $value ) {
		return $this->set_prop( 'cancelled', $value );
	}

	/**
	 * Set the total amount failed via this form.
	 *
	 * @since 1.0.19
	 * @param  float $value Amount cancelled.
	 * @return array
	 */
	public function set_failed( $value ) {
		return $this->set_prop( 'failed', $value );
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
        return (bool) apply_filters( 'wpinv_is_default_payment_form', $is_default, $this->ID, $this );
	}

    /**
	 * Checks whether the form is active.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
    public function is_active() {
        $is_active = null !== $this->get_id();

        if ( ! current_user_can( 'edit_post', $this->ID ) && $this->post_status != 'publish' ) {
            $is_active = false;
        }

        return (bool) apply_filters( 'wpinv_is_payment_form_active', $is_active, $this );
    }

}
