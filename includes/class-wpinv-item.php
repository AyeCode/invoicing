<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Item Class
 *
 */
class WPInv_Item  extends GetPaid_Data {

    /**
	 * Which data store to load.
	 *
	 * @var string
	 */
    protected $data_store_name = 'item';

    /**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'item';

    /**
	 * Item Data array. This is the core item data exposed in APIs.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $data = array(
		'parent_id'            => 0,
		'status'               => 'draft',
		'version'              => '',
		'date_created'         => null,
        'date_modified'        => null,
        'name'                 => '',
        'description'          => '',
        'author'               => 1,
        'price'                => 0,
        'vat_rule'             => 'digital',
        'vat_class'            => '_standard',
        'type'                 => 'custom',
        'custom_id'            => null,
        'custom_name'          => null,
        'custom_singular_name' => null,
        'is_editable'          => 1,
        'is_dynamic_pricing'   => null,
        'minimum_price'        => null,
        'is_recurring'         => null,
        'recurring_period'     => null,
        'recurring_interval'   => null,
        'recurring_limit'      => null,
        'is_free_trial'        => null,
        'trial_period'         => null,
        'trial_interval'       => null,
    );

    /**
	 * Stores meta in cache for future reads.
	 *
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'getpaid_items';

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

		if ( ! empty( $item ) && is_numeric( $item ) && 'wpi_item' == get_post_type( $item ) ) {
			$this->set_id( $item );
		} elseif ( $item instanceof self ) {
			$this->set_id( $item->get_id() );
		} elseif ( ! empty( $item->ID ) ) {
			$this->set_id( $item->ID );
		} elseif ( is_scalar( $item ) && $item_id = self::get_item_id_by_field( $item, 'custom_id' ) ) {
			$this->set_id( $item_id );
		} elseif ( is_scalar( $item ) && $item_id = self::get_item_id_by_field( $item, 'name' ) ) {
			$this->set_id( $item_id );
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
	 * Get parent item ID.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_parent_id( $context = 'view' ) {
		return (int) $this->get_prop( 'parent_id', $context );
    }

    /**
	 * Get item status.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
    }

    /**
	 * Get plugin version when the item was created.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_version( $context = 'view' ) {
		return $this->get_prop( 'version', $context );
    }

    /**
	 * Get date when the item was created.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
    }

    /**
	 * Get GMT date when the item was created.
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
	 * Get date when the item was last modified.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
    }

    /**
	 * Get GMT date when the item was last modified.
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
	 * Get the item name.
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
	 * Get the item description.
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
	 * Get the owner of the item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_author( $context = 'view' ) {
		return (int) $this->get_prop( 'author', $context );
	}
	
	/**
	 * Alias of self::get_author().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_owner( $context = 'view' ) {
		return $this->get_author( $context );
    }

    /**
	 * Get the price of the item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_price( $context = 'view' ) {
        return wpinv_sanitize_amount( $this->get_prop( 'price', $context ) );
	}

	/**
	 * Get the inital price of the item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_initial_price( $context = 'view' ) {

		$price = (float) $this->get_price( $context );

		if ( $this->has_free_trial() ) {
			$price = 0;
		}

        return wpinv_sanitize_amount( apply_filters( 'wpinv_get_initial_item_price', $price, $this ) );
    }

    /**
	 * Returns a formated price.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
    public function get_the_price() {
        return wpinv_price( $this->get_price() );
	}

	/**
	 * Returns the formated initial price.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
    public function get_the_initial_price() {
        return wpinv_price( $this->get_initial_price() );
    }

    /**
	 * Get the VAT rule of the item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_vat_rule( $context = 'view' ) {
        return $this->get_prop( 'vat_rule', $context );
    }

    /**
	 * Get the VAT class of the item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_vat_class( $context = 'view' ) {
        return $this->get_prop( 'vat_class', $context );
    }

    /**
	 * Get the type of the item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
        return $this->get_prop( 'type', $context );
    }

    /**
	 * Get the custom id of the item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_custom_id( $context = 'view' ) {
        return $this->get_prop( 'custom_id', $context );
    }

    /**
	 * Get the custom name of the item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_custom_name( $context = 'view' ) {
        return $this->get_prop( 'custom_name', $context );
    }

    /**
	 * Get the custom singular name of the item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_custom_singular_name( $context = 'view' ) {
        return $this->get_prop( 'custom_singular_name', $context );
    }

    /**
	 * Checks if an item is editable..
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_is_editable( $context = 'view' ) {
        return (int) $this->get_prop( 'is_editable', $context );
    }

    /**
	 * Alias of self::get_is_editable().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_editable( $context = 'view' ) {
		return $this->get_is_editable( $context );
    }

    /**
	 * Checks if dynamic pricing is enabled.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_is_dynamic_pricing( $context = 'view' ) {
        return (int) $this->get_prop( 'is_dynamic_pricing', $context );
    }

    /**
	 * Returns the minimum price if dynamic pricing is enabled.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_minimum_price( $context = 'view' ) {
        return wpinv_sanitize_amount( $this->get_prop( 'minimum_price', $context ) );
    }

    /**
	 * Checks if this is a recurring item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_is_recurring( $context = 'view' ) {
        return (int) $this->get_prop( 'is_recurring', $context );
	}
	
	/**
	 * Get the recurring price of the item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_recurring_price( $context = 'view' ) {
		$price = $this->get_price( $context );
        return wpinv_sanitize_amount( apply_filters( 'wpinv_get_recurring_item_price', $price, $this->ID ) );
	}

	/**
	 * Get the formatted recurring price of the item.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
    public function get_the_recurring_price() {
        return wpinv_price( $this->get_recurring_price() );
	}

	/**
	 * Get the first renewal date (in timestamps) of the item.
	 *
	 * @since 1.0.19
	 * @return int
	 */
	public function get_first_renewal_date() {

		$periods = array(
			'D' => 'days',
			'W' => 'weeks',
			'M' => 'months',
			'Y' => 'years',
		);

		$period   = $this->get_recurring_period();
		$interval = $this->get_recurring_interval();

		if ( $this->has_free_trial() ) {
			$period   = $this->get_trial_period();
			$interval = $this->get_trial_interval();
		}

		$period       = $periods[ $period ];
		$interval     = empty( $interval ) ? 1 : $interval;
		$next_renewal = strtotime( "+$interval $period", current_time( 'timestamp' ) );
        return apply_filters( 'wpinv_get_first_renewal_date', $next_renewal, $this );
    }

    /**
	 * Get the recurring period.
	 *
	 * @since 1.0.19
	 * @param  bool $full Return abbreviation or in full.
	 * @return string
	 */
	public function get_recurring_period( $full = false ) {
        $period = $this->get_prop( 'recurring_period', 'view' );

        if ( $full && ! is_bool( $full ) ) {
            $full = false;
        }

        return getpaid_sanitize_recurring_period( $period, $full );
    }

    /**
	 * Get the recurring interval.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_recurring_interval( $context = 'view' ) {
		$interval = absint( $this->get_prop( 'recurring_interval', $context ) );
		return max( 1, $interval );
    }

    /**
	 * Get the recurring limit.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_recurring_limit( $context = 'view' ) {
        return (int) $this->get_prop( 'recurring_limit', $context );
    }

    /**
	 * Checks if we have a free trial.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_is_free_trial( $context = 'view' ) {
        return (int) $this->get_prop( 'is_free_trial', $context );
    }

    /**
	 * Alias for self::get_is_free_trial().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_free_trial( $context = 'view' ) {
        return $this->get_is_free_trial( $context );
    }

    /**
	 * Get the trial period.
	 *
	 * @since 1.0.19
	 * @param  bool $full Return abbreviation or in full.
	 * @return string
	 */
	public function get_trial_period( $full = false ) {
        $period = $this->get_prop( 'trial_period', 'view' );

        if ( $full && ! is_bool( $full ) ) {
            $full = false;
        }

        return getpaid_sanitize_recurring_period( $period, $full );
    }

    /**
	 * Get the trial interval.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_trial_interval( $context = 'view' ) {
        return (int) $this->get_prop( 'trial_interval', $context );
	}
	
	/**
	 * Get the item's edit url.
	 *
	 * @since 1.0.19
	 * @return string
	 */
	public function get_edit_url() {
        return get_edit_post_link( $this->get_id(), 'edit' );
	}

	/**
	 * Given an item's name/custom id, it returns its id.
	 *
	 *
	 * @static
	 * @param string $value The item name or custom id.
	 * @param string $field Either name or custom_id.
	 * @param string $type in case you need to search for a given type.
	 * @since 1.0.15
	 * @return int
	 */
	public static function get_item_id_by_field( $value, $field = 'custom_id', $type = '' ) {

		// Trim the value.
		$value = sanitize_text_field( $value );
		if ( empty( $value ) ) {
			return 0;
		}

        // Valid fields.
        $fields = array( 'custom_id', 'name', 'slug' );

		// Ensure a field has been passed.
		if ( empty( $field ) || ! in_array( $field, $fields ) ) {
			return 0;
		}

		if ( $field == 'name' ) {
			$field = 'slug';
		}

		// Maybe retrieve from the cache.
		$item_id = wp_cache_get( $value, "getpaid_{$type}_item_{$field}s_to_item_ids" );
		if ( ! empty( $item_id ) ) {
			return $item_id;
		}

		// Fetch from the db.
		$items = array();
		if ( $field == 'slug' ) {
			$items = get_posts(
				array(
					'post_type'      => 'wpi_item',
					'name'           => $value,
					'posts_per_page' => 1,
					'post_status'    => 'any',
				)
			);
		}

		if ( $field =='custom_id' ) {
			$items = get_posts(
				array(
					'post_type'      => 'wpi_item',
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'meta_query'     => array(
						array(
							'key'   => '_wpinv_type',
                			'value' => $type,
						),
						array(
							'key'   => '_wpinv_custom_id',
                			'value' => $value,
						)
					)
				)
			);
		}

		if ( empty( $items ) ) {
			return 0;
		}

		// Update the cache with our data
		wp_cache_set( $value, $items[0]->ID, "getpaid_{$type}_item_{$field}s_to_item_ids" );

		return $items[0]->ID;
    }

    /**
     * Margic method for retrieving a property.
     */
    public function __get( $key ) {

        // Check if we have a helper method for that.
        if ( method_exists( $this, 'get_' . $key ) ) {
            return call_user_func( array( $this, 'get_' . $key ) );
        }

        // Check if the key is in the associated $post object.
        if ( ! empty( $this->post ) && isset( $this->post->$key ) ) {
            return $this->post->$key;
        }

        return $this->get_prop( $key );

    }

    /*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting item data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
    */

    /**
	 * Set parent order ID.
	 *
	 * @since 1.0.19
	 */
	public function set_parent_id( $value ) {
		if ( $value && ( $value === $this->get_id() || ! get_post( $value ) ) ) {
			return;
		}
		$this->set_prop( 'parent_id', absint( $value ) );
	}

    /**
	 * Sets item status.
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
	 * Set the item description.
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
	 * Set the owner of the item.
	 *
	 * @since 1.0.19
	 * @param  int $value New author.
	 */
	public function set_author( $value ) {
		$this->set_prop( 'author', (int) $value );
	}
	
	/**
	 * Alias of self::set_author().
	 *
	 * @since 1.0.19
	 * @param  int $value New author.
	 */
	public function set_owner( $value ) {
		$this->set_author( $value );
    }

    /**
	 * Set the price of the item.
	 *
	 * @since 1.0.19
	 * @param  float $value New price.
	 */
	public function set_price( $value ) {
        $this->set_prop( 'price', (float) wpinv_sanitize_amount( $value ) );
    }

    /**
	 * Set the VAT rule of the item.
	 *
	 * @since 1.0.19
	 * @param  string $value new rule.
	 */
	public function set_vat_rule( $value ) {
        $this->set_prop( 'vat_rule', $value );
    }

    /**
	 * Set the VAT class of the item.
	 *
	 * @since 1.0.19
	 * @param  string $value new class.
	 */
	public function set_vat_class( $value ) {
        $this->set_prop( 'vat_class', $value );
    }

    /**
	 * Set the type of the item.
	 *
	 * @since 1.0.19
	 * @param  string $value new item type.
	 * @return string
	 */
	public function set_type( $value ) {

        if ( empty( $value ) ) {
            $value = 'custom';
        }

        $this->set_prop( 'type', $value );
    }

    /**
	 * Set the custom id of the item.
	 *
	 * @since 1.0.19
	 * @param  string $value new custom id.
	 */
	public function set_custom_id( $value ) {
        $this->set_prop( 'custom_id', $value );
    }

    /**
	 * Set the custom name of the item.
	 *
	 * @since 1.0.19
	 * @param  string $value new custom name.
	 */
	public function set_custom_name( $value ) {
        $this->set_prop( 'custom_name', $value );
    }

    /**
	 * Set the custom singular name of the item.
	 *
	 * @since 1.0.19
	 * @param  string $value new custom singular name.
	 */
	public function set_custom_singular_name( $value ) {
        $this->set_prop( 'custom_singular_name', $value );
    }

    /**
	 * Sets if an item is editable..
	 *
	 * @since 1.0.19
	 * @param  int|bool $value whether or not the item is editable.
	 */
	public function set_is_editable( $value ) {
		$this->set_prop( 'is_editable', (int) $value );
    }

    /**
	 * Sets if dynamic pricing is enabled.
	 *
	 * @since 1.0.19
	 * @param  int|bool $value whether or not dynamic pricing is allowed.
	 */
	public function set_is_dynamic_pricing( $value ) {
        $this->set_prop( 'is_dynamic_pricing', (int) $value );
    }

    /**
	 * Sets the minimum price if dynamic pricing is enabled.
	 *
	 * @since 1.0.19
	 * @param  float $value minimum price.
	 */
	public function set_minimum_price( $value ) {
        $this->set_prop( 'minimum_price',  (float) wpinv_sanitize_amount( $value ) );
    }

    /**
	 * Sets if this is a recurring item.
	 *
	 * @since 1.0.19
	 * @param  int|bool $value whether or not dynamic pricing is allowed.
	 */
	public function set_is_recurring( $value ) {
        $this->set_prop( 'is_recurring', (int) $value );
    }

    /**
	 * Set the recurring period.
	 *
	 * @since 1.0.19
	 * @param  string $value new period.
	 */
	public function set_recurring_period( $value ) {
        $this->set_prop( 'recurring_period', $value );
    }

    /**
	 * Set the recurring interval.
	 *
	 * @since 1.0.19
	 * @param  int $value recurring interval.
	 */
	public function set_recurring_interval( $value ) {
        return $this->set_prop( 'recurring_interval', (int) $value );
    }

    /**
	 * Get the recurring limit.
	 * @since 1.0.19
	 * @param  int $value The recurring limit.
	 * @return int
	 */
	public function set_recurring_limit( $value ) {
        $this->set_prop( 'recurring_limit', (int) $value );
    }

    /**
	 * Checks if we have a free trial.
	 *
	 * @since 1.0.19
	 * @param  int|bool $value whether or not it has a free trial.
	 */
	public function set_is_free_trial( $value ) {
        $this->set_prop( 'is_free_trial', (int) $value );
    }

    /**
	 * Set the trial period.
	 *
	 * @since 1.0.19
	 * @param  string $value trial period.
	 */
	public function set_trial_period( $value ) {
        $this->set_prop( 'trial_period', $value );
    }

    /**
	 * Set the trial interval.
	 *
	 * @since 1.0.19
	 * @param  int $value trial interval.
	 */
	public function set_trial_interval( $value ) {
        $this->set_prop( 'trial_interval', $value );
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
	 * Checks whether the item has enabled dynamic pricing.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function user_can_set_their_price() {
        return (bool) $this->get_is_dynamic_pricing();
	}
	
	/**
	 * Checks whether the item is recurring.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function is_recurring() {
        return (bool) $this->get_is_recurring();
    }

    /**
	 * Checks whether the item has a free trial.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
    public function has_free_trial() {
        $has_trial = $this->is_recurring() && (bool) $this->get_free_trial() ? true : false;
        return (bool) apply_filters( 'wpinv_item_has_free_trial', $has_trial, $this->ID, $this );
    }

    /**
	 * Checks whether the item is free.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
    public function is_free() {
        $is_free   = $this->get_price() == 0;
        return (bool) apply_filters( 'wpinv_is_free_item', $is_free, $this->ID, $this );
    }

    /**
	 * Checks the item status against a passed in status.
	 *
	 * @param array|string $status Status to check.
	 * @return bool
	 */
	public function has_status( $status ) {
		$has_status = ( is_array( $status ) && in_array( $this->get_status(), $status, true ) ) || $this->get_status() === $status;
		return (bool) apply_filters( 'getpaid_item_has_status', $has_status, $this, $status );
    }

    /**
	 * Checks the item type against a passed in types.
	 *
	 * @param array|string $type Type to check.
	 * @return bool
	 */
	public function is_type( $type ) {
		$is_type = ( is_array( $type ) && in_array( $this->get_type(), $type, true ) ) || $this->get_type() === $type;
		return (bool) apply_filters( 'getpaid_item_is_type', $is_type, $this, $type );
	}

    /**
	 * Checks whether the item is editable.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
    public function is_editable() {
        $is_editable = $this->get_is_editable();
        return (bool) apply_filters( 'wpinv_item_is_editable', $is_editable, $this->ID, $this );
	}

	/**
	 * Returns an array of cart fees.
	 */
	public function get_fees() {
        return array();
    }

    /**
	 * Checks whether the item is purchasable.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
    public function can_purchase() {
        $can_purchase = $this->exists();

        if ( ! current_user_can( 'edit_post', $this->ID ) && $this->post_status != 'publish' ) {
            $can_purchase = false;
        }

        return (bool) apply_filters( 'wpinv_can_purchase_item', $can_purchase, $this );
    }

    /**
	 * Checks whether the item supports dynamic pricing.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
    public function supports_dynamic_pricing() {
        return (bool) apply_filters( 'wpinv_item_supports_dynamic_pricing', true, $this );
    }
}
