<?php
/**
 * GetPaid Data Store.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Data store class.
 */
class GetPaid_Data_Store {

	/**
	 * Contains an instance of the data store class that we are working with.
	 *
	 * @var GetPaid_Data_Store
	 */
	private $instance = null;

	/**
	 * Contains an array of default supported data stores.
	 * Format of object name => class name.
	 * Example: 'item' => 'GetPaid_Item_Data_Store'
	 * You can also pass something like item-<type> for item stores and
	 * that type will be used first when available, if a store is requested like
	 * this and doesn't exist, then the store would fall back to 'item'.
	 * Ran through `getpaid_data_stores`.
	 *
	 * @var array
	 */
	private $stores = array(
		'item'         => 'GetPaid_Item_Data_Store',
		'payment_form' => 'GetPaid_Payment_Form_Data_Store',
		'discount'     => 'GetPaid_Discount_Data_Store',
		'invoice'      => 'GetPaid_Invoice_Data_Store',
		'subscription' => 'GetPaid_Subscription_Data_Store',
	);

	/**
	 * Contains the name of the current data store's class name.
	 *
	 * @var string
	 */
	private $current_class_name = '';

	/**
	 * The object type this store works with.
	 *
	 * @var string
	 */
	private $object_type = '';

	/**
	 * Tells GetPaid_Data_Store which object
	 * store we want to work with.
	 *
	 * @param string $object_type Name of object.
	 */
	public function __construct( $object_type ) {
		$this->object_type = $object_type;
		$this->stores      = apply_filters( 'getpaid_data_stores', $this->stores );

		// If this object type can't be found, check to see if we can load one
		// level up (so if item-type isn't found, we try item).
		if ( ! array_key_exists( $object_type, $this->stores ) ) {
			$pieces      = explode( '-', $object_type );
			$object_type = $pieces[0];
		}

		if ( array_key_exists( $object_type, $this->stores ) ) {
			$store = apply_filters( 'getpaid_' . $object_type . '_data_store', $this->stores[ $object_type ] );
			if ( is_object( $store ) ) {
				$this->current_class_name = get_class( $store );
				$this->instance           = $store;
			} else {
				if ( ! class_exists( $store ) ) {
					throw new Exception( __( 'Data store class does not exist.', 'invoicing' ) );
				}
				$this->current_class_name = $store;
				$this->instance           = new $store();
			}
		} else {
			throw new Exception( __( 'Invalid data store.', 'invoicing' ) );
		}
	}

	/**
	 * Only store the object type to avoid serializing the data store instance.
	 *
	 * @return array
	 */
	public function __sleep() {
		return array( 'object_type' );
	}

	/**
	 * Re-run the constructor with the object type.
	 *
	 * @throws Exception When validation fails.
	 */
	public function __wakeup() {
		$this->__construct( $this->object_type );
	}

	/**
	 * Loads a data store.
	 *
	 * @param string $object_type Name of object.
	 *
	 * @since 1.0.19
	 * @throws Exception When validation fails.
	 * @return GetPaid_Data_Store
	 */
	public static function load( $object_type ) {
		return new GetPaid_Data_Store( $object_type );
	}

	/**
	 * Returns the class name of the current data store.
	 *
	 * @since 1.0.19
	 * @return string
	 */
	public function get_current_class_name() {
		return $this->current_class_name;
	}

	/**
	 * Returns the object type of the current data store.
	 *
	 * @since 1.0.19
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Reads an object from the data store.
	 *
	 * @since 1.0.19
	 * @param GetPaid_Data $data GetPaid data instance.
	 */
	public function read( &$data ) {
		$this->instance->read( $data );
	}

	/**
	 * Create an object in the data store.
	 *
	 * @since 1.0.19
	 * @param GetPaid_Data $data GetPaid data instance.
	 */
	public function create( &$data ) {
		$this->instance->create( $data );
	}

	/**
	 * Update an object in the data store.
	 *
	 * @since 1.0.19
	 * @param GetPaid_Data $data GetPaid data instance.
	 */
	public function update( &$data ) {
		$this->instance->update( $data );
	}

	/**
	 * Delete an object from the data store.
	 *
	 * @since 1.0.19
	 * @param GetPaid_Data $data GetPaid data instance.
	 * @param array   $args Array of args to pass to the delete method.
	 */
	public function delete( &$data, $args = array() ) {
		$this->instance->delete( $data, $args );
	}

	/**
	 * Data stores can define additional function. This passes
	 * through to the instance if that function exists.
	 *
	 * @since 1.0.19
	 * @param string $method     Method.
	 * @return mixed
	 */
	public function __call( $method, $parameters ) {
		if ( is_callable( array( $this->instance, $method ) ) ) {
			$object     = array_shift( $parameters );
			$parameters = array_merge( array( &$object ), $parameters );
			return call_user_func_array( array( $this->instance, $method ), $parameters );
		}
	}

}
