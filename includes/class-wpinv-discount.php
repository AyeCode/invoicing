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
 * @property string $code
 * @property string $description
 * @property string $type
 * @property string $type_name
 * @property string $expiration
 * @property string $start
 * @property string $status
 * @property string $date_modified
 * @property string $date_created
 * @property array $items
 * @property array $excluded_items
 * @property int $uses
 * @property int $max_uses
 * @property bool $is_recurring
 * @property bool $is_single_use
 * @property float $min_total
 * @property float $max_total
 * @property float $amount
 *
 */
class WPInv_Discount {
	
	/**
	 * Discount ID.
	 *
	 * @since 1.0.15
	 * @var integer|null
	 */
	public $ID = null;

	/**
	 * Old discount status.
	 *
	 * @since 1.0.15
	 * @var string
	 */
	public $old_status = 'draft';
	
	/**
	 * Data array.
	 *
	 * @since 1.0.15
	 * @var array
	 */
	protected $data = array();

	/**
	 * Discount constructor.
	 *
	 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
	 * @since 1.0.15
	 */
	public function __construct( $discount = array() ) {
        
        // If the discount is an instance of this class...
		if ( $discount instanceof WPInv_Discount ) {
			$this->init( $discount->data );
			return;
        }
        
        // If the discount is an array of discount details...
        if ( is_array( $discount ) ) {
			$this->init( $discount );
			return;
		}
		
		// Try fetching the discount by its post id.
		$data = false;
		
		if ( ! empty( $discount ) && is_numeric( $discount ) ) {
			$discount = absint( $discount );
			$data = self::get_data_by( 'id', $discount );
		}

		if ( is_array( $data ) ) {
			$this->init( $data );
			return;
		}
		
		// Try fetching the discount by its discount code.
		if ( ! empty( $discount ) && is_string( $discount ) ) {
			$data = self::get_data_by( 'discount_code', $discount );
		}

		if ( is_array( $data ) ) {
			$this->init( $data );
			return;
		} 
		
		// If we are here then the discount does not exist.
		$this->init( array() );
	}
	
	/**
	 * Sets up object properties
	 *
	 * @since 1.0.15
	 * @param array $data An array containing the discount's data
	 */
	public function init( $data ) {
		$data       	  = self::sanitize_discount_data( $data );
		$this->data 	  = $data;
		$this->old_status = $data['status'];
		$this->ID   	  = $data['ID'];
	}
	
	/**
	 * Fetch a discount from the db/cache
	 *
	 *
	 * @static
	 * @param string $field The field to query against: 'ID', 'discount_code'
	 * @param string|int $value The field value
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
		
		$return = self::sanitize_discount_data( $return );
		$return = apply_filters( 'wpinv_discount_properties', $return );

		// Update the cache with our data
		wp_cache_add( $discount->ID, $return, 'WPInv_Discounts' );
		wp_cache_add( $return['code'], $discount->ID, 'WPInv_Discount_Codes' );

		return $return;
	}
	
	/**
	 * Sanitizes discount data
	 *
	 * @static
	 * @since 1.0.15
	 * @access public
	 *
	 * @return array the sanitized data
	 */
	public static function sanitize_discount_data( $data ) {
		
		$allowed_discount_types = array_keys( wpinv_get_discount_types() );
		
		$return = array(
            'ID'                          => null,
            'code'                        => '',
            'amount'                      => 0,
            'date_created'                => current_time('mysql'),
            'date_modified'               => current_time('mysql'),
			'expiration'                  => null,
			'start'                  	  => current_time('mysql'),
			'status'                  	  => 'draft',
            'type'               		  => 'percent',
            'description'                 => '',
            'uses'                        => 0,
            'is_single_use'               => false,
            'items'              		  => array(),
            'excluded_items'              => array(),
            'max_uses'                    => 0,
            'is_recurring'                => false,
            'min_total'                   => '',
			'max_total'              	  => '',
		);
		
				
		// Arrays only please.
		if ( ! is_array( $data ) ) {
            return $return;
        }

		// If an id is provided, ensure it is a valid discount.
        if ( ! empty( $data['ID'] ) && ( ! is_numeric( $data['ID'] ) || 'wpi_discount' !== get_post_type( $data['ID'] ) ) ) {
            return $return;
		}

        $return = array_merge( $return, $data );

        // Sanitize some keys.
        $return['amount']         = wpinv_sanitize_amount( $return['amount'] );
		$return['is_single_use']  = (bool) $return['is_single_use'];
		$return['is_recurring']   = (bool) $return['is_recurring'];
		$return['uses']	          = (int) $return['uses'];
		$return['max_uses']	      = (int) $return['max_uses'];
		$return['min_total'] 	  = wpinv_sanitize_amount( $return['min_total'] );
        $return['max_total'] 	  = wpinv_sanitize_amount( $return['max_total'] );

		// Trim all values.
		$return = wpinv_clean( $return );
		
		// Ensure the discount type is supported.
        if ( ! in_array( $return['type'], $allowed_discount_types, true ) ) {
            $return['type'] = 'percent';
		}
		$return['type_name'] = wpinv_get_discount_type_name( $return['type'] );
		
		// Do not offer more than a 100% discount.
		if ( $return['type'] == 'percent' && (float) $return['amount'] > 100 ) {
			$return['amount'] = 100;
		}

		// Format dates.
		foreach( wpinv_parse_list( 'date_created date_modified expiration start') as $prop ) {
			if( ! empty( $return[$prop] ) ) {
				$return[$prop]      = date_i18n( 'Y-m-d H:i:s', strtotime( $return[$prop] ) );
			}
		}

		// Formart items.
		foreach( array( 'excluded_items', 'items' ) as $prop ) {

			if( ! empty( $return[$prop] ) ) {
				// Ensure that the property is an array of non-empty integers.
				$return[$prop]      = array_filter( array_map( 'intval', wpinv_parse_list( $return[$prop] ) ) );
			} else {
				$return[$prop]      = array();
			}

		}
		
		return apply_filters( 'wpinv_sanitize_discount_data', $return, $data );
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
	
	/**
	 * Magic method for accessing discount properties.
	 *
	 * @since 1.0.15
	 * @access public
	 *
	 * @param string $key Discount data to retrieve
	 * @return mixed Value of the given discount property (if set).
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Magic method for accessing discount properties.
	 *
	 * @since 1.0.15
	 * @access public
	 *
	 * @param string $key Discount data to retrieve
	 * @return mixed Value of the given discount property (if set).
	 */
	public function get( $key ) {
		
		if ( $key == 'id' ) {
			$key = 'ID';
		}
		
		if( method_exists( $this, "get_$key") ) {
			$value 	= call_user_func( array( $this, "get_$key" ) );
		} else if( isset( $this->data[$key] ) ) {
			$value 	= $this->data[$key];
		} else {
			$value = null;
		}
		
		/**
		 * Filters a discount's property value.
		 * 
		 * The dynamic part ($key) can be any property name e.g items, code, type etc.
		 * 
		 * @param mixed          $value    The property's value.
		 * @param int            $ID       The discount's ID.
		 * @param WPInv_Discount $discount The discount object.
		 * @param string         $code     The discount's discount code.
		 * @param array          $data     The discount's data array.
		 */
		return apply_filters( "wpinv_get_discount_{$key}", $value, $this->ID, $this, $this->data['code'], $this->data );

	}
	
	/**
	 * Magic method for setting discount fields.
	 *
	 * This method does not update custom fields in the database.
	 *
	 * @since 1.0.15
	 * @access public
	 *
	 */
	public function __set( $key, $value ) {
		
		if ( 'id' == strtolower( $key ) ) {
			
			$this->ID = $value;
			$this->data['ID'] = $value;
			return;
			
		}
		
		/**
		 * Filters a discount's property value before it is saved.
		 * 
		 * 
		 * 
		 * The dynamic part ($key) can be any property name e.g items, code, type etc.
		 * 
		 * @param mixed          $value    The property's value.
		 * @param int            $ID       The discount's ID.
		 * @param WPInv_Discount $discount The discount object.
		 * @param string         $code     The discount's discount code.
		 * @param array          $data     The discount's data array.
		 */
		$value = apply_filters( "wpinv_set_discount_{$key}", $value, $this->ID, $this, $this->code, $this->data );

		if( method_exists( $this, "set_$key") ) {
			call_user_func( array( $this, "set_$key" ), $value );
		} else {
			$this->data[$key] = $value;
		}
		
	}
	
	/**
	 * Saves (or updates) a discount to the database
	 *
	 * @since 1.0.15
	 * @access public
	 * @return bool
	 *
	 */
	public function save(){
		
		$data = self::sanitize_discount_data( $this->data );

		// Should we create a new post?
		if( ! $data[ 'ID' ] ) {

			$id = wp_insert_post( array(
				'post_status'           => $data['status'],
				'post_type'             => 'wpi_discount',
				'post_excerpt'          => $data['description'],
			) );

			if( empty( $id ) ) {
				return false;
			}

			$data[ 'ID' ] = (int) $id;
			$this->ID = $data[ 'ID' ];
			$this->data['ID'] = $data[ 'ID' ];

		} else {
			$this->update_status( $data['status'] );
		}

		$meta = apply_filters( 'wpinv_update_discount', $data, $this->ID, $this );

		do_action( 'wpinv_pre_update_discount', $meta, $this->ID, $this );

		foreach( wpinv_parse_list( 'ID date_created date_modified status description type_name' ) as $prop ) {
			if ( isset( $meta[$prop] ) ) {
				unset( $meta[$prop] );
			}
		}

		if( isset( $meta['uses'] ) && empty( $meta['uses'] ) ) {
			unset( $meta['uses'] );
		}

		// Save the metadata.
		foreach( $meta as $key => $value ) {
			update_post_meta( $this->ID, "_wpi_discount_$key", $value );
		}

		$this->refresh();

		do_action( 'wpinv_post_update_discount', $meta, $this->ID );

		return true;		
	}

	/**
	 * Refreshes the discount data.
	 *
	 * @since 1.0.15
	 * @access public
	 * @return bool
	 *
	 */
	public function refresh(){

		// Empty the cache for this discount.
		wp_cache_delete( $this->ID, 'WPInv_Discounts' );
		wp_cache_delete( $this->get( 'code' ), 'WPInv_Discount_Codes' );

		$data = self::get_data_by( 'id', $this->ID );
		if( is_array( $data ) ) {
			$this->init( $data );
		} else {
			$this->init( array() );
		}

	}

	/**
	 * Saves (or updates) a discount to the database
	 *
	 * @since 1.0.15
	 * @access public
	 * @return bool
	 *
	 */
	public function update_status( $status = 'publish' ){


		if ( $this->exists() && $this->old_status != $status ) {

			do_action( 'wpinv_pre_update_discount_status', $this->ID, $this->old_status, $status );
        	$updated = wp_update_post( array( 'ID' => $this->ID, 'post_status' => $status ) );
			do_action( 'wpinv_post_update_discount_status', $this->ID, $this->old_status, $status );

			$this->refresh();

			return $updated !== 0;
			
		}

		return false;		
	}
	
	
	/**
	 * Checks whether a discount exists in the database or not
	 * 
	 * @since 1.0.15
	 */
	public function exists(){
		return ! empty( $this->ID );
	}
	
	// Boolean methods
	
	/**
	 * Checks the discount type.
	 * 
	 * 
	 * @param  string $type the discount type to check against
	 * @since 1.0.15
	 * @return bool
	 */
	public function is_type( $type ) {
		return $this->type == $type;
	}
	
	/**
	 * Checks whether the discount is published or not
	 * 
	 * @since 1.0.15
	 * @return bool
	 */
	public function is_active() {
		return $this->status == 'publish';
	}
	
	/**
	 * Checks whether the discount is has exided the usage limit or not
	 * 
	 * @since 1.0.15
	 * @return bool
	 */
	public function has_exceeded_limit() {
		if( empty( $this->max_uses ) || empty( $this->uses ) ) { 
			return false ;
		}
		
		$exceeded =  $this->uses >= $this->max_uses;
		return apply_filters( 'wpinv_is_discount_maxed_out', $exceeded, $this->ID, $this, $this->code );
	}
	
	/**
	 * Checks if the discount is expired
	 * 
	 * @since 1.0.15
	 * @return bool
	 */
	public function is_expired() {
		$expired = empty ( $this->expiration ) ? false : current_time( 'timestamp' ) > strtotime( $this->expiration );
		return apply_filters( 'wpinv_is_discount_expired', $expired, $this->ID, $this, $this->code );
	}

	/**
	 * Checks the discount start date.
	 * 
	 * @since 1.0.15
	 * @return bool
	 */
	public function has_started() {
		$started = empty ( $this->start ) ? true : current_time( 'timestamp' ) > strtotime( $this->start );
		return apply_filters( 'wpinv_is_discount_started', $started, $this->ID, $this, $this->code );		
	}
	
	/**
	 * Check if a discount is valid for a given item id.
	 *
	 * @param  int|int[]  $item_ids
	 * @since 1.0.15
	 * @return boolean
	 */
	public function is_valid_for_items( $item_ids ) {
		 
		$item_ids = array_map( 'intval',  wpinv_parse_list( $item_ids ) );
		$included = array_intersect( $item_ids, $this->items );
		$excluded = array_intersect( $item_ids, $this->excluded_items );

		if( ! empty( $this->excluded_items ) && ! empty( $excluded ) ) {
			return false;
		}

		if( ! empty( $this->items ) && empty( $included ) ) {
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
	 * Checks if the minimum amount is met
	 *
	 * @param  float  $amount The amount to check against
	 * @since 1.0.15
	 * @return boolean
	 */
	public function is_minimum_amount_met( $amount ) {
		$amount = floatval( $amount );
		$min_met= ! ( $this->min_total > 0 && $amount < $this->min_total );
		return apply_filters( 'wpinv_is_discount_min_met', $min_met, $this->ID, $this, $this->code, $amount );
	}

	/**
	 * Checks if the maximum amount is met
	 *
	 * @param  float  $amount The amount to check against
	 * @since 1.0.15
	 * @return boolean
	 */
	public function is_maximum_amount_met( $amount ) {
		$amount = floatval( $amount );
		$max_met= ! ( $this->max_total > 0 && $amount > $this->max_total );
		return apply_filters( 'wpinv_is_discount_max_met', $max_met, $this->ID, $this, $this->code, $amount );
	}

	/**
	 * Check if a discount is valid for the given user
	 *
	 * @param  int|string  $user
	 * @since 1.0.15
	 * @return boolean
	 */
	public function is_valid_for_user( $user ) {
		global $wpi_checkout_id;

		if( empty( $user ) || empty( $this->is_single_use ) ) {
			return true;
		}

		$user_id = 0;
        if ( is_int( $user ) ) {
            $user_id = absint( $user );
        } else if ( is_email( $user ) && $user_data = get_user_by( 'email', $user ) ) {
            $user_id = $user_data->ID;
        } else if ( $user_data = get_user_by( 'login', $user ) ) {
            $user_id = $user_data->ID;
        } else if ( absint( $user ) > 0 ) {
            $user_id = absint( $user );
		}

		if ( empty( $user_id ) ) {
			return true;
		}
		
		// Get all payments with matching user id
        $payments = wpinv_get_invoices( array( 'user' => $user_id, 'limit' => false ) ); 
		$code     = strtolower( $this->code );

		foreach ( $payments as $payment ) {

			// Don't count discount used for current invoice checkout.
			if ( ! empty( $wpi_checkout_id ) && $wpi_checkout_id == $payment->ID ) {
				continue;
			}
			
			if ( $payment->has_status( array( 'wpi-cancelled', 'wpi-failed' ) ) ) {
				continue;
			}

			$discounts = $payment->get_discounts( true );
			if ( empty( $discounts ) ) {
				continue;
			}

			$discounts = array_map( 'strtolower', wpinv_parse_list( $discounts ) );
			if ( ! empty( $discounts ) && in_array( $code, $discounts ) ) {
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

		if ( empty( $this->ID ) ) {
			return true;
		}

		do_action( 'wpinv_pre_delete_discount', $this->ID, $this->data );
		wp_cache_delete( $this->ID, 'WPInv_Discounts' );
    	wp_delete_post( $this->ID, true );
		wp_cache_delete( $this->code, 'WPInv_Discount_Codes' );
    	do_action( 'wpinv_post_delete_discount', $this->ID, $this->data );

		$this->ID = null;
		$this->data['id'] = null;
		return true;
	}

	/**
	 * Increases a discount's usage.
	 *
	 * @since 1.0.15
	 * @param int $by The number of usages to increas by.
	 * @return int
	 */
	public function increase_usage( $by = 1 ) {

		$this->uses = $this->uses + $by;

		if( $this->uses  < 0 ) {
			$this->uses = 0;
			update_post_meta( $this->ID, "_wpi_discount_uses", 0 );
		}

		$this->save();

		if( $by > 0 ) {
			do_action( 'wpinv_discount_increase_use_count', $this->uses, $this->ID, $this->code, $by );
		} else {
			do_action( 'wpinv_discount_decrease_use_count', $this->uses, $this->ID, $this->code, absint( $by ) );
		}
		
		return $this->uses;
	}

	/**
	 * Retrieves discount data
	 *
	 * @since 1.0.15
	 * @return array
	 */
	public function get_data() {
		$return = array();
		foreach( array_keys( $this->data ) as $key ) {
			$return[ $key ] = $this->get( $key );
		}
		return $return;
	}

	/**
	 * Retrieves discount data as json
	 *
	 * @since 1.0.15
	 * @return string|false
	 */
	public function get_data_as_json() {
		return wp_json_encode( $this->get_data() );
	}

	/**
	 * Checks if a discount can only be used once per user.
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function get_is_single_use() {
		return (bool) apply_filters( 'wpinv_is_discount_single_use', $this->data['is_single_use'], $this->ID, $this, $this->code );
	}

	/**
	 * Checks if a discount is recurring.
	 *
	 * @since 1.0.15
	 * @return bool
	 */
	public function get_is_recurring() {
		return (bool) apply_filters( 'wpinv_is_discount_recurring', $this->data['is_recurring'], $this->ID, $this->code, $this );
	}

	/**
	 * Returns a discount's included items.
	 *
	 * @since 1.0.15
	 * @return array
	 */
	public function get_items() {
		return wpinv_parse_list( apply_filters( 'wpinv_get_discount_item_reqs', $this->data['items'], $this->ID, $this, $this->code ) );
	}

	/**
	 * Returns a discount's discounted amount.
	 *
	 * @since 1.0.15
	 * @return float
	 */
	public function get_discounted_amount( $amount ) {

		if ( $this->type == 'flat' ) {
            $amount = $amount - $this->amount;
		} else {
            $amount = $amount - ( $amount * ( $this->amount / 100 ) );
		}

		if ( $amount < 0 ) {
			$amount = 0;
		}

		return apply_filters( 'wpinv_discounted_amount', $amount, $this->ID, $this, $this->code, $this->amount );
	}
	
}
