<?php
/**
 * Contains Discount calculation class
 *
 * @since   1.0.15
 */

defined( 'ABSPATH' ) || exit;

/**
 * Discount class.
 *
 * @since 1.0.15
 *
 */
class WPInv_Discount extends GetPaid_Data  {

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
    protected $data_store_name = 'discount';

    /**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'discount';

	/**
	 * Discount Data array. This is the core item data exposed in APIs.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $data = array(
		'status'               => 'draft',
		'version'              => '',
		'date_created'         => null,
        'date_modified'        => null,
        'name'                 => 'no-name',
        'description'          => '',
        'author'               => 1,
        'code'                 => null,
        'type'                 => 'percent',
        'expiration'           => null,
        'start'                => null,
        'items'                => array(),
        'excluded_items'       => array(),
        'uses' 				   => 0,
        'max_uses'             => null,
        'is_recurring'         => null,
        'is_single_use'        => null,
        'min_total'            => null,
        'max_total'            => null,
        'amount'               => null,
    );

	/**
	 * Stores meta in cache for future reads.
	 *
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'getpaid_discounts';

    /**
     * Stores a reference to the original WP_Post object
     *
     * @var WP_Post
     */
	protected $post = null;

	/**
	 * Get the discount if ID is passed, otherwise the discount is new and empty.
	 *
	 * @param int|array|string|WPInv_Discount|WP_Post $discount discount data, object, ID or code.
	 */
	public function __construct( $discount = 0 ) {
		parent::__construct( $discount );

		if ( is_numeric( $discount ) && 'wpi_discount' === get_post_type( $discount ) ) {
			$this->set_id( $discount );
		} elseif ( $discount instanceof self ) {
			$this->set_id( $discount->get_id() );
		} elseif ( ! empty( $discount->ID ) ) {
			$this->set_id( $discount->ID );
		} elseif ( is_array( $discount ) ) {
			$this->set_props( $discount );

			if ( isset( $discount['ID'] ) ) {
				$this->set_id( $discount['ID'] );
			}

		} elseif ( is_scalar( $discount ) && $discount = self::get_discount_id_by_code( $discount ) ) {
			$this->set_id( $discount );
		} else {
			$this->set_object_read( true );
		}

        // Load the datastore.
		$this->data_store = GetPaid_Data_Store::load( $this->data_store_name );

		if ( $this->get_id() > 0 ) {
            $this->post = get_post( $this->get_id() );
            $this->ID   = $this->get_id();
			$this->data_store->read( $this );
        }

	}

	/**
	 * Fetch a discount from the db/cache
	 *
	 *
	 * @static
	 * @param string $field The field to query against: 'ID', 'discount_code'
	 * @param string|int $value The field value
	 * @deprecated
	 * @since 1.0.15
	 * @return array|bool array of discount details on success. False otherwise.
	 */
	public static function get_data_by( $field, $value ) {

		if ( 'id' == strtolower( $field ) ) {
			// Make sure the value is numeric to avoid casting objects, for example,
			// to int 1.
			if ( ! is_numeric( $value ) )
				return false;
			$value = intval( $value );
			if ( $value < 1 )
				return false;
		}

		if ( ! $value || ! is_string( $field ) ) {
			return false;
		}

		$field = trim( $field );

		// prepare query args
		switch ( strtolower( $field ) ) {
			case 'id':
				$discount_id = $value;
				$args		 = array( 'include' => array( $value ) );
				break;
			case 'discount_code':
			case 'code':
				$value       = trim( $value );
				$discount_id = wp_cache_get( $value, 'WPInv_Discount_Codes' );
				$args		 = array( 'meta_key' => '_wpi_discount_code', 'meta_value' => $value );
				break;
			case 'name':
				$discount_id = 0;
				$args		 = array( 'name' => trim( $value ) );
				break;
			default:
				$args		 = apply_filters( "wpinv_discount_get_data_by_{$field}_args", null, $value );
				if ( ! is_array( $args ) ) {
					return apply_filters( "wpinv_discount_get_data_by_$field", false, $value );
				}

		}

		// Check if there is a cached value.
		if ( ! empty( $discount_id ) && $discount = wp_cache_get( (int) $discount_id, 'WPInv_Discounts' ) ) {
			return $discount;
		}

		$args = array_merge(
			$args,
			array(
				'post_type'      => 'wpi_discount',
				'posts_per_page' => 1,
				'post_status'    => array( 'publish', 'pending', 'draft', 'expired' )
			)
		);

		$discount = get_posts( $args );

		if( empty( $discount ) ) {
			return false;
		}

		$discount = $discount[0];

		// Prepare the return data.
		$return = array(
            'ID'                          => $discount->ID,
            'code'                        => get_post_meta( $discount->ID, '_wpi_discount_code', true ),
            'amount'                      => get_post_meta( $discount->ID, '_wpi_discount_amount', true ),
            'date_created'                => $discount->post_date,
			'date_modified'               => $discount->post_modified,
			'status'               		  => $discount->post_status,
			'start'                  	  => get_post_meta( $discount->ID, '_wpi_discount_start', true ),
            'expiration'                  => get_post_meta( $discount->ID, '_wpi_discount_expiration', true ),
            'type'               		  => get_post_meta( $discount->ID, '_wpi_discount_type', true ),
            'description'                 => $discount->post_excerpt,
            'uses'                 		  => get_post_meta( $discount->ID, '_wpi_discount_uses', true ),
            'is_single_use'               => get_post_meta( $discount->ID, '_wpi_discount_is_single_use', true ),
            'items'              	      => get_post_meta( $discount->ID, '_wpi_discount_items', true ),
            'excluded_items'              => get_post_meta( $discount->ID, '_wpi_discount_excluded_items', true ),
            'max_uses'                    => get_post_meta( $discount->ID, '_wpi_discount_max_uses', true ),
            'is_recurring'                => get_post_meta( $discount->ID, '_wpi_discount_is_recurring', true ),
            'min_total'                   => get_post_meta( $discount->ID, '_wpi_discount_min_total', true ),
            'max_total'                   => get_post_meta( $discount->ID, '_wpi_discount_max_total', true ),
        );

		$return = apply_filters( 'wpinv_discount_properties', $return );

		// Update the cache with our data
		wp_cache_add( $discount->ID, $return, 'WPInv_Discounts' );
		wp_cache_add( $return['code'], $discount->ID, 'WPInv_Discount_Codes' );

		return $return;
	}

	/**
	 * Given a discount code, it returns a discount id.
	 *
	 *
	 * @static
	 * @param string $discount_code
	 * @since 1.0.15
	 * @return int
	 */
	public static function get_discount_id_by_code( $discount_code ) {

		// Trim the code.
		$discount_code = trim( $discount_code );

		// Ensure a value has been passed.
		if ( empty( $discount_code ) ) {
			return 0;
		}

		// Maybe retrieve from the cache.
		$discount_id   = wp_cache_get( $discount_code, 'getpaid_discount_codes' );
		if ( ! empty( $discount_id ) ) {
			return $discount_id;
		}

		// Fetch the first discount codes.
		$discounts = get_posts(
			array(
				'meta_key'       => '_wpi_discount_code',
				'meta_value'     => $discount_code,
				'post_type'      => 'wpi_discount',
				'posts_per_page' => 1,
				'post_status'    => array( 'publish', 'pending', 'draft', 'expired' ),
				'fields'         => 'ids',
			)
		);

		if ( empty( $discounts ) ) {
			return 0;
		}

		$discount_id = $discounts[0];

		// Update the cache with our data
		wp_cache_add( get_post_meta( $discount_id, '_wpi_discount_code', true ), $discount_id, 'getpaid_discount_codes' );

		return $discount_id;
	}

	/**
	 * Magic method for checking the existence of a certain custom field.
	 *
	 * @since 1.0.15
	 * @access public
	 *
	 * @return bool Whether the given discount field is set.
	 */
	public function __isset( $key ){
		return isset( $this->data[$key] ) || method_exists( $this, "get_$key");
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete discounts from the database.
	|
    */

    /*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get discount status.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
    }

    /**
	 * Get plugin version when the discount was created.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_version( $context = 'view' ) {
		return $this->get_prop( 'version', $context );
    }

    /**
	 * Get date when the discount was created.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
    }

    /**
	 * Get GMT date when the discount was created.
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
	 * Get date when the discount was last modified.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
    }

    /**
	 * Get GMT date when the discount was last modified.
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
	 * Get the discount name.
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
	 * Get the discount description.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		return $this->get_prop( 'description', $context );
    }

    /**
	 * Alias of self::get_description().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_excerpt( $context = 'view' ) {
		return $this->get_description( $context );
    }

    /**
	 * Alias of self::get_description().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_summary( $context = 'view' ) {
		return $this->get_description( $context );
    }

    /**
	 * Get the owner of the discount.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_author( $context = 'view' ) {
		return (int) $this->get_prop( 'author', $context );
	}

	/**
	 * Get the discount code.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_code( $context = 'view' ) {
		return $this->get_prop( 'code', $context );
	}

	/**
	 * Alias for self::get_code().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_coupon_code( $context = 'view' ) {
		return $this->get_code( $context );
	}

	/**
	 * Alias for self::get_code().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_discount_code( $context = 'view' ) {
		return $this->get_code( $context );
	}

	/**
	 * Get the discount's amount.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_amount( $context = 'view' ) {
		return $context == 'view' ? floatval( $this->get_prop( 'amount', $context ) ) : $this->get_prop( 'amount', $context );
	}

	/**
	 * Get the discount's formated amount/rate.
	 *
	 * @since 1.0.19
	 * @return string
	 */
	public function get_formatted_amount() {

		if ( $this->is_type( 'flat' ) ) {
			$rate = wpinv_price( $this->get_amount() );
		} else {
			$rate = $this->get_amount() . '%';
		}

		return apply_filters( 'wpinv_format_discount_rate', $rate, $this->get_type(), $this->get_amount() );
	}

	/**
	 * Get the discount's start date.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_start( $context = 'view' ) {
		return $this->get_prop( 'start', $context );
	}

	/**
	 * Alias for self::get_start().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_start_date( $context = 'view' ) {
		return $this->get_start( $context );
	}

	/**
	 * Get the discount's expiration date.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_expiration( $context = 'view' ) {
		return $this->get_prop( 'expiration', $context );
	}

	/**
	 * Alias for self::get_expiration().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_expiration_date( $context = 'view' ) {
		return $this->get_expiration( $context );
	}

	/**
	 * Alias for self::get_expiration().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_end_date( $context = 'view' ) {
		return $this->get_expiration( $context );
	}

	/**
	 * Get the discount's type.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	/**
	 * Get the number of times a discount has been used.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_uses( $context = 'view' ) {
		return (int) $this->get_prop( 'uses', $context );
	}

	/**
	 * Get the discount's usage, i.e uses / max uses.
	 *
	 * @since 1.0.19
	 * @return string
	 */
	public function get_usage() {

		if ( ! $this->has_limit() ) {
			return $this->get_uses() . ' / ' . ' &infin;';
		}

		return $this->get_uses() . ' / ' . (int) $this->get_max_uses();

	}

	/**
	 * Get the maximum number of time a discount can be used.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_max_uses( $context = 'view' ) {
		$max_uses = $this->get_prop( 'max_uses', $context );
		return empty( $max_uses ) ? null : $max_uses;
	}

	/**
	 * Checks if this is a single use discount or not.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return bool
	 */
	public function get_is_single_use( $context = 'view' ) {
		return $this->get_prop( 'is_single_use', $context );
	}

	/**
	 * Get the items that can be used with this discount.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return array
	 */
	public function get_items( $context = 'view' ) {
		return array_filter( wp_parse_id_list( $this->get_prop( 'items', $context ) ) );
	}

	/**
	 * Alias for self::get_items().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return array
	 */
	public function get_allowed_items( $context = 'view' ) {
		return $this->get_items( $context );
	}

	/**
	 * Get the items that are not allowed to use this discount.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return array
	 */
	public function get_excluded_items( $context = 'view' ) {
		return array_filter( wp_parse_id_list( $this->get_prop( 'excluded_items', $context ) ) );
	}

	/**
	 * Checks if this is a recurring discount or not.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int|string|bool
	 */
	public function get_is_recurring( $context = 'view' ) {
		return $this->get_prop( 'is_recurring', $context );
	}

	/**
	 * Get's the minimum total amount allowed for this discount.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_min_total( $context = 'view' ) {
		$minimum = $this->get_prop( 'min_total', $context );
		return empty( $minimum ) ? null : $minimum;
	}

	/**
	 * Alias for self::get_min_total().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_minimum_total( $context = 'view' ) {
		return $this->get_min_total( $context );
	}

	/**
	 * Get's the maximum total amount allowed for this discount.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_max_total( $context = 'view' ) {
		$maximum = $this->get_prop( 'max_total', $context );
		return empty( $maximum ) ? null : $maximum;
	}

	/**
	 * Alias for self::get_max_total().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_maximum_total( $context = 'view' ) {
		return $this->get_max_total( $context );
	}

	/**
	 * Magic method for accessing discount properties.
	 *
	 * @since 1.0.15
	 * @access public
	 *
	 * @param string $key Discount data to retrieve
	 * @param  string $context View or edit context.
	 * @return mixed Value of the given discount property (if set).
	 */
	public function get( $key, $context = 'view' ) {
        return $this->get_prop( $key, $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting discount data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	*/

	/**
	 * Sets discount status.
	 *
	 * @since 1.0.19
	 * @param  string $status New status.
	 * @return array details of change.
	 */
	public function set_status( $status ) {
        $old_status = $this->get_status();

        $this->set_prop( 'status', $status );

		return array(
			'from' => $old_status,
			'to'   => $status,
		);
    }

    /**
	 * Set plugin version when the discount was created.
	 *
	 * @since 1.0.19
	 */
	public function set_version( $value ) {
		$this->set_prop( 'version', $value );
    }

    /**
	 * Set date when the discount was created.
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
	 * Set date when the discount was last modified.
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
	 * Set the discount name.
	 *
	 * @since 1.0.19
	 * @param  string $value New name.
	 */
	public function set_name( $value ) {
        $name = sanitize_text_field( $value );
		$this->set_prop( 'name', $name );
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
	 * Set the discount description.
	 *
	 * @since 1.0.19
	 * @param  string $value New description.
	 */
	public function set_description( $value ) {
        $description = wp_kses_post( $value );
		return $this->set_prop( 'description', $description );
    }

    /**
	 * Alias of self::set_description().
	 *
	 * @since 1.0.19
	 * @param  string $value New description.
	 */
	public function set_excerpt( $value ) {
		$this->set_description( $value );
    }

    /**
	 * Alias of self::set_description().
	 *
	 * @since 1.0.19
	 * @param  string $value New description.
	 */
	public function set_summary( $value ) {
		$this->set_description( $value );
    }

    /**
	 * Set the owner of the discount.
	 *
	 * @since 1.0.19
	 * @param  int $value New author.
	 */
	public function set_author( $value ) {
		$this->set_prop( 'author', (int) $value );
	}

	/**
	 * Sets the discount code.
	 *
	 * @since 1.0.19
	 * @param string $value New discount code.
	 */
	public function set_code( $value ) {
		$code = sanitize_text_field( $value );
		$this->set_prop( 'code', $code );
	}

	/**
	 * Alias of self::set_code().
	 *
	 * @since 1.0.19
	 * @param string $value New discount code.
	 */
	public function set_coupon_code( $value ) {
		$this->set_code( $value );
	}

	/**
	 * Alias of self::set_code().
	 *
	 * @since 1.0.19
	 * @param string $value New discount code.
	 */
	public function set_discount_code( $value ) {
		$this->set_code( $value );
	}

	/**
	 * Sets the discount amount.
	 *
	 * @since 1.0.19
	 * @param float $value New discount code.
	 */
	public function set_amount( $value ) {
		$amount = floatval( wpinv_sanitize_amount( $value ) );
		$this->set_prop( 'amount', $amount );
	}

	/**
	 * Sets the discount's start date.
	 *
	 * @since 1.0.19
	 * @param float $value New start date.
	 */
	public function set_start( $value ) {
		$date = strtotime( $value );

        if ( $date ) {
            $this->set_prop( 'start', date( 'Y-m-d H:i', $date ) );
            return true;
		}

		$this->set_prop( 'start', '' );

        return false;
	}

	/**
	 * Alias of self::set_start().
	 *
	 * @since 1.0.19
	 * @param string $value New start date.
	 */
	public function set_start_date( $value ) {
		$this->set_start( $value );
	}

	/**
	 * Sets the discount's expiration date.
	 *
	 * @since 1.0.19
	 * @param float $value New expiration date.
	 */
	public function set_expiration( $value ) {
		$date = strtotime( $value );

        if ( $date ) {
            $this->set_prop( 'expiration', date( 'Y-m-d H:i', $date ) );
            return true;
        }

		$this->set_prop( 'expiration', '' );
        return false;
	}

	/**
	 * Alias of self::set_expiration().
	 *
	 * @since 1.0.19
	 * @param string $value New expiration date.
	 */
	public function set_expiration_date( $value ) {
		$this->set_expiration( $value );
	}

	/**
	 * Alias of self::set_expiration().
	 *
	 * @since 1.0.19
	 * @param string $value New expiration date.
	 */
	public function set_end_date( $value ) {
		$this->set_expiration( $value );
	}

	/**
	 * Sets the discount type.
	 *
	 * @since 1.0.19
	 * @param string $value New discount type.
	 */
	public function set_type( $value ) {
		if ( $value && array_key_exists( sanitize_text_field( $value ), wpinv_get_discount_types() ) ) {
			$this->set_prop( 'type', sanitize_text_field( $value ) );
		}
	}

	/**
	 * Sets the number of times a discount has been used.
	 *
	 * @since 1.0.19
	 * @param int $value usage count.
	 */
	public function set_uses( $value ) {

		$value = (int) $value;

		if ( $value < 0 ) {
			$value = 0;
		}

		$this->set_prop( 'uses', (int) $value );
	}

	/**
	 * Sets the maximum number of times a discount can be used.
	 *
	 * @since 1.0.19
	 * @param int $value maximum usage count.
	 */
	public function set_max_uses( $value ) {
		$this->set_prop( 'max_uses', absint( $value ) );
	}

	/**
	 * Sets if this is a single use discount or not.
	 *
	 * @since 1.0.19
	 * @param int|bool $value is single use.
	 */
	public function set_is_single_use( $value ) {
		$this->set_prop( 'is_single_use', (bool) $value );
	}

	/**
	 * Sets the items that can be used with this discount.
	 *
	 * @since 1.0.19
	 * @param array $value items.
	 */
	public function set_items( $value ) {
		$this->set_prop( 'items', array_filter( wp_parse_id_list( $value ) ) );
	}

	/**
	 * Alias for self::set_items().
	 *
	 * @since 1.0.19
	 * @param array $value items.
	 */
	public function set_allowed_items( $value ) {
		$this->set_items( $value );
	}

	/**
	 * Sets the items that can not be used with this discount.
	 *
	 * @since 1.0.19
	 * @param array $value items.
	 */
	public function set_excluded_items( $value ) {
		$this->set_prop( 'excluded_items', array_filter( wp_parse_id_list( $value ) ) );
	}

	/**
	 * Sets if this is a recurring discounts or not.
	 *
	 * @since 1.0.19
	 * @param int|bool $value is recurring.
	 */
	public function set_is_recurring( $value ) {
		$this->set_prop( 'is_recurring', (bool) $value );
	}

	/**
	 * Sets the minimum total that can not be used with this discount.
	 *
	 * @since 1.0.19
	 * @param float $value minimum total.
	 */
	public function set_min_total( $value ) {
		$this->set_prop( 'min_total', (float) wpinv_sanitize_amount( $value ) );
	}

	/**
	 * Alias for self::set_min_total().
	 *
	 * @since 1.0.19
	 * @param float $value minimum total.
	 */
	public function set_minimum_total( $value ) {
		$this->set_min_total( $value );
	}

	/**
	 * Sets the maximum total that can not be used with this discount.
	 *
	 * @since 1.0.19
	 * @param float $value maximum total.
	 */
	public function set_max_total( $value ) {
		$this->set_prop( 'max_total', (float) wpinv_sanitize_amount( $value ) );
	}

	/**
	 * Alias for self::set_max_total().
	 *
	 * @since 1.0.19
	 * @param float $value maximum total.
	 */
	public function set_maximum_total( $value ) {
		$this->set_max_total( $value );
	}

	/**
	 * @deprecated
	 */
	public function refresh(){}

	/**
	 * @deprecated
	 *
	 */
	public function update_status( $status = 'publish' ){

		if ( $this->exists() && $this->get_status() != $status ) {
			$this->set_status( $status );
			$this->save();
		}

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
	 * Checks whether a discount exists in the database or not
	 *
	 * @since 1.0.15
	 */
	public function exists(){
		$id = $this->get_id();
		return ! empty( $id );
	}

	/**
	 * Checks the discount type.
	 *
	 *
	 * @param  string $type the discount type to check against
	 * @since 1.0.15
	 * @return bool
	 */
	public function is_type( $type ) {
		return $this->get_type() == $type;
	}

	/**
	 * Checks whether the discount is published or not
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function is_active() {
		return $this->get_status() == 'publish';
	}

	/**
	 * Checks whether the discount has max uses
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function has_limit() {
		$limit = $this->get_max_uses();
		return ! empty( $limit );
	}

	/**
	 * Checks whether the discount has ever been used.
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function has_uses() {
		return $this->get_uses() > 0;
	}

	/**
	 * Checks whether the discount is has exided the usage limit or not
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function has_exceeded_limit() {

		if ( ! $this->has_limit() || ! $this->has_uses() ) {
			$exceeded = false ;
		} else {
			$exceeded = (int) $this->get_max_uses() <= $this->get_uses();
		}

		return apply_filters( 'wpinv_is_discount_maxed_out', $exceeded, $this->get_id(), $this, $this->get_code() );
	}

	/**
	 * Checks whether the discount has an expiration date.
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function has_expiration_date() {
		$date = $this->get_expiration_date();
		return ! empty( $date );
	}

	/**
	 * Checks if the discount is expired
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function is_expired() {
		$expired = $this->has_expiration_date() ? current_time( 'timestamp' ) > strtotime( $this->get_expiration_date() ) : false;
		return apply_filters( 'wpinv_is_discount_expired', $expired, $this->get_id(), $this, $this->get_code() );
	}

	/**
	 * Checks whether the discount has a start date.
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function has_start_date() {
		$date = $this->get_start_date();
		return ! empty( $date );
	}

	/**
	 * Checks the discount start date.
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function has_started() {
		$started = $this->has_start_date() ? true : current_time( 'timestamp' ) > strtotime( $this->get_start_date() );
		return apply_filters( 'wpinv_is_discount_started', $started, $this->get_id(), $this, $this->get_code() );
	}

	/**
	 * Checks the discount has allowed items or not.
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function has_allowed_items() {
		$allowed_items = $this->get_allowed_items();
		return ! empty( $allowed_items );
	}

	/**
	 * Checks the discount has excluded items or not.
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function has_excluded_items() {
		$excluded_items = $this->get_excluded_items();
		return ! empty( $excluded_items );
	}

	/**
	 * Check if a discount is valid for a given item id.
	 *
	 * @param  int|int[]  $item_ids
	 * @since 1.0.15
	 * @return boolean
	 */
	public function is_valid_for_items( $item_ids ) {

		$item_ids = array_filter( wp_parse_id_list( $item_ids ) );
		$included = array_intersect( $item_ids, $this->get_allowed_items() );
		$excluded = array_intersect( $item_ids, $this->get_excluded_items() );

		if ( $this->has_excluded_items() && ! empty( $excluded ) ) {
			return false;
		}

		if ( $this->has_allowed_items() && empty( $included ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if a discount is valid for the given amount
	 *
	 * @param  float  $amount The amount to check against
	 * @since 1.0.15
	 * @return boolean
	 */
	public function is_valid_for_amount( $amount ) {
		return $this->is_minimum_amount_met( $amount ) && $this->is_maximum_amount_met( $amount );
	}

	/**
	 * Checks if the minimum amount is set
	 *
	 * @since 1.0.15
	 * @return boolean
	 */
	public function has_minimum_amount() {
		$minimum = $this->get_minimum_total();
		return ! empty( $minimum );
	}

	/**
	 * Checks if the minimum amount is met
	 *
	 * @param  float  $amount The amount to check against
	 * @since 1.0.15
	 * @return boolean
	 */
	public function is_minimum_amount_met( $amount ) {
		$amount = floatval( wpinv_sanitize_amount( $amount ) );
		$min_met= ! ( $this->has_minimum_amount() && $amount < floatval( wpinv_sanitize_amount( $this->get_minimum_total() ) ) );
		return apply_filters( 'wpinv_is_discount_min_met', $min_met, $this->get_id(), $this, $this->get_code(), $amount );
	}

	/**
	 * Checks if the maximum amount is set
	 *
	 * @since 1.0.15
	 * @return boolean
	 */
	public function has_maximum_amount() {
		$maximum = $this->get_maximum_total();
		return ! empty( $maximum );
	}

	/**
	 * Checks if the maximum amount is met
	 *
	 * @param  float  $amount The amount to check against
	 * @since 1.0.15
	 * @return boolean
	 */
	public function is_maximum_amount_met( $amount ) {
		$amount = floatval( wpinv_sanitize_amount( $amount ) );
		$max_met= ! ( $this->has_maximum_amount() && $amount > floatval( wpinv_sanitize_amount( $this->get_maximum_total() ) ) );
		return apply_filters( 'wpinv_is_discount_max_met', $max_met, $this->get_id(), $this, $this->get_code(), $amount );
	}

	/**
	 * Checks if the discount is recurring.
	 *
	 * @since 1.0.15
	 * @return boolean
	 */
	public function is_recurring() {
		$recurring = $this->get_is_recurring();
		return ! empty( $recurring );
	}

	/**
	 * Checks if the discount is single use.
	 *
	 * @since 1.0.15
	 * @return boolean
	 */
	public function is_single_use() {
		$usage = $this->get_is_single_use();
		return ! empty( $usage );
	}

	/**
	 * Check if a discount is valid for the given user
	 *
	 * @param  int|string  $user
	 * @since 1.0.15
	 * @return boolean
	 */
	public function is_valid_for_user( $user ) {

		// Ensure that the discount is single use.
		if ( empty( $user ) || ! $this->is_single_use() ) {
			return true;
		}

		// Prepare the user id.
		$user_id = 0;
        if ( is_numeric( $user ) ) {
            $user_id = absint( $user );
        } else if ( is_email( $user ) && $user_data = get_user_by( 'email', $user ) ) {
            $user_id = $user_data->ID;
        } else if ( $user_data = get_user_by( 'login', $user ) ) {
            $user_id = $user_data->ID;
        }

		// Ensure that we have a user.
		if ( empty( $user_id ) ) {
			return true;
		}

		// Get all payments with matching user id.
        $payments = wpinv_get_invoices( array( 'user' => $user_id, 'limit' => false, 'paginate' => false ) );
		$code     = strtolower( $this->get_code() );

		// For each payment...
		foreach ( $payments as $payment ) {

			// Only check for paid invoices.
			if ( $payment->is_paid() && strtolower( $payment->get_discount_code() ) == $code ) {
				return false;
			}

		}

		return true;
	}

	/**
	 * Deletes the discount from the database
	 *
	 * @since 1.0.15
	 * @return boolean
	 */
	public function remove() {
		return $this->delete();
	}

	/**
	 * Increases a discount's usage.
	 *
	 * @since 1.0.15
	 * @param int $by The number of usages to increas by.
	 * @return int
	 */
	public function increase_usage( $by = 1 ) {

		// Abort if zero.
		if ( empty( $by ) ) {
			return;
		}

		// Increase the usage.
		$this->set_uses( $this->get_uses() + (int) $by );

		// Save the discount.
		$this->save();

		// Fire relevant hooks.
		if( (int) $by > 0 ) {
			do_action( 'wpinv_discount_increase_use_count', $this->get_uses(), $this->get_id(), $this->get_code(),  absint( $by ) );
		} else {
			do_action( 'wpinv_discount_decrease_use_count', $this->get_uses(), $this->get_id(), $this->get_code(), absint( $by ) );
		}

		// Return the number of times the discount has been used.
		return $this->get_uses();
	}

	/**
	 * Alias of self::__toString()
	 *
	 * @since 1.0.15
	 * @return string|false
	 */
	public function get_data_as_json() {
		return $this->__toString();
	}

	/**
	 * Returns a discount's discounted amount.
	 *
	 * @since 1.0.15
	 * @param float $amount
	 * @return float
	 */
	public function get_discounted_amount( $amount ) {

		// Convert amount to float.
		$amount = (float) $amount;

		// Get discount amount.
		$discount_amount = $this->get_amount();

		if ( empty( $discount_amount ) ) {
			return 0;
		}

		// Format the amount.
		$discount_amount = floatval( wpinv_sanitize_amount( $discount_amount ) );

		// If this is a percentage discount.
		if ( $this->is_type( 'percent' ) ) {
            $discount_amount = $amount * ( $discount_amount / 100 );
		}

		// Discount can not be less than zero...
		if ( $discount_amount < 0 ) {
			$discount_amount = 0;
		}

		// ... or more than the amount.
		if ( $discount_amount > $amount ) {
			$discount_amount = $amount;
		}

		return apply_filters( 'wpinv_discount_total_discount_amount', $discount_amount, $amount, $this );
	}

}
