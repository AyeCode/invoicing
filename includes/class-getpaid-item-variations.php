<?php
/**
 * Item Variations (Variable Pricing).
 *
 * @version 2.8.47
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles item variations — variable pricing tiers per item.
 *
 * @since 2.8.47
 */
class GetPaid_Item_Variations {

	/**
	 * Active variation contexts keyed by item_id.
	 *
	 * @var array
	 */
	private static $active_variations = array();

	/**
	 * License meta keys that variations can override, mapped to variation array keys.
	 *
	 * @var array
	 */
	private static $license_meta_map = array(
		'getpaid_license_manager_maximum_activations' => 'max_activations',
		'getpaid_license_manager_interval'            => 'license_interval',
		'getpaid_license_manager_period'              => 'license_period',
		'getpaid_license_manager_license_prefix'      => 'license_prefix',
	);

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Checkout.
		add_filter( 'getpaid_payment_form_submission_processed_item', array( $this, 'apply_variation_to_item' ), 10, 2 );
		add_filter( 'getpaid_submission_data', array( $this, 'inject_variation_from_request' ), 10, 2 );

		// Frontend.
		add_action( 'getpaid_before_payment_form_cart', array( $this, 'render_variation_selectors' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Invoice line items.
		add_action( 'getpaid_before_invoice_line_item_actions', array( $this, 'display_line_item_variation' ), 10, 2 );
		add_filter( 'getpaid_admin_invoice_line_item_summary', array( $this, 'admin_line_item_variation' ), 10, 3 );

		// Variation context: set at priority 5, clear at 999 (single cycle for all integrations).
		add_action( 'getpaid_invoice_status_publish', array( $this, 'set_variation_contexts' ), 5 );
		add_action( 'getpaid_subscription_active', array( $this, 'set_subscription_variation_contexts' ), 5 );
		add_action( 'getpaid_subscription_trialling', array( $this, 'set_subscription_variation_contexts' ), 5 );
		add_action( 'getpaid_invoice_status_publish', array( __CLASS__, 'clear_active_variations' ), 999 );
		add_action( 'getpaid_subscription_active', array( __CLASS__, 'clear_active_variations' ), 999 );
		add_action( 'getpaid_subscription_trialling', array( __CLASS__, 'clear_active_variations' ), 999 );

		// Integration filters.
		add_filter( 'get_post_metadata', array( $this, 'filter_license_meta' ), 10, 4 );
		add_filter( 'getpaid_get_item_downloads', array( $this, 'filter_item_downloads' ), 10, 2 );

		// Admin.
		if ( is_admin() ) {
			add_action( 'wpinv_item_details_metabox_item_details', array( 'GetPaid_Meta_Box_Item_Variations', 'output' ) );
			add_action( 'getpaid_item_metabox_save', array( 'GetPaid_Meta_Box_Item_Variations', 'save' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( 'GetPaid_Meta_Box_Item_Variations', 'enqueue_scripts' ) );
			add_action( 'wpinv_item_info_metabox', array( $this, 'render_variation_shortcodes' ) );
		}
	}

	/**
	 * Checks if an item has variations enabled.
	 *
	 * @since 2.8.47
	 *
	 * @param int $item_id Item post ID.
	 * @return bool
	 */
	public static function item_has_variations( $item_id ) {
		return (bool) get_post_meta( $item_id, '_wpinv_has_variations', true );
	}

	/**
	 * Gets all variations for an item.
	 *
	 * @since 2.8.47
	 *
	 * @param int $item_id Item post ID.
	 * @return array
	 */
	public static function get_item_variations( $item_id ) {
		if ( ! self::item_has_variations( $item_id ) ) {
			return array();
		}

		$variations = get_post_meta( $item_id, '_wpinv_item_variations', true );

		return is_array( $variations ) ? $variations : array();
	}

	/**
	 * Gets a specific variation by its slug.
	 *
	 * @since 2.8.47
	 *
	 * @param int    $item_id    Item post ID.
	 * @param string $variation_id Variation slug.
	 * @return array|false Variation data or false if not found.
	 */
	public static function get_variation_by_id( $item_id, $variation_id ) {
		foreach ( self::get_item_variations( $item_id ) as $variation ) {
			if ( isset( $variation['id'] ) && $variation['id'] === $variation_id ) {
				return $variation;
			}
		}

		return false;
	}

	/**
	 * Gets the default variation for an item. Falls back to the first variation.
	 *
	 * @since 2.8.47
	 *
	 * @param int $item_id Item post ID.
	 * @return array|false Variation data or false if none exist.
	 */
	public static function get_default_variation( $item_id ) {
		$variations = self::get_item_variations( $item_id );

		foreach ( $variations as $variation ) {
			if ( ! empty( $variation['is_default'] ) ) {
				return $variation;
			}
		}

		return ! empty( $variations ) ? $variations[0] : false;
	}

	/**
	 * Extracts the variation data stored in an invoice item's meta.
	 *
	 * @since 2.8.47
	 *
	 * @param GetPaid_Form_Item|array $item Invoice item or cart_details array.
	 * @return array|false Variation data or false.
	 */
	public static function get_invoice_item_variation( $item ) {
		if ( is_object( $item ) && method_exists( $item, 'get_item_meta' ) ) {
			$meta = $item->get_item_meta();
		} elseif ( is_array( $item ) && isset( $item['meta'] ) ) {
			$meta = maybe_unserialize( $item['meta'] );
		} else {
			return false;
		}

		return ! empty( $meta['variation_data'] ) && is_array( $meta['variation_data'] ) ? $meta['variation_data'] : false;
	}

	/**
	 * Sanitizes a single variation data array.
	 *
	 * @since 2.8.47
	 *
	 * @param array $variation Raw variation data.
	 * @return array Sanitized variation data.
	 */
	public static function sanitize_variation( $variation ) {
		return array(
			'id'                 => sanitize_key( ! empty( $variation['id'] ) ? $variation['id'] : '' ),
			'name'               => sanitize_text_field( ! empty( $variation['name'] ) ? $variation['name'] : '' ),
			'price'              => max( 0, (float) wpinv_sanitize_amount( ! empty( $variation['price'] ) ? $variation['price'] : 0 ) ),
			'is_recurring'       => ! empty( $variation['is_recurring'] ) ? 1 : 0,
			'recurring_period'   => sanitize_text_field( ! empty( $variation['recurring_period'] ) ? $variation['recurring_period'] : 'Y' ),
			'recurring_interval' => absint( ! empty( $variation['recurring_interval'] ) ? $variation['recurring_interval'] : 1 ),
			'recurring_limit'    => absint( ! empty( $variation['recurring_limit'] ) ? $variation['recurring_limit'] : 0 ),
			'is_default'         => ! empty( $variation['is_default'] ) ? 1 : 0,
			'max_activations'    => sanitize_text_field( ! empty( $variation['max_activations'] ) ? $variation['max_activations'] : '' ),
			'license_prefix'     => sanitize_text_field( ! empty( $variation['license_prefix'] ) ? $variation['license_prefix'] : '' ),
			'license_interval'   => sanitize_text_field( ! empty( $variation['license_interval'] ) ? $variation['license_interval'] : '' ),
			'license_period'     => sanitize_text_field( ! empty( $variation['license_period'] ) ? $variation['license_period'] : '' ),
			'download_ids'       => ! empty( $variation['download_ids'] ) ? array_map( 'sanitize_text_field', (array) $variation['download_ids'] ) : array(),
		);
	}

	/**
	 * Sanitizes and validates an array of variations. Deduplicates IDs, ensures one default.
	 *
	 * @since 2.8.47
	 *
	 * @param array $variations Raw variations array.
	 * @return array Sanitized variations.
	 */
	public static function sanitize_variations( $variations ) {
		if ( ! is_array( $variations ) ) {
			return array();
		}

		$sanitized   = array();
		$has_default = false;
		$used_ids    = array();

		foreach ( $variations as $variation ) {
			$variation = self::sanitize_variation( $variation );

			if ( empty( $variation['id'] ) || empty( $variation['name'] ) ) {
				continue;
			}

			$original_id = $variation['id'];
			$suffix      = 2;

			while ( in_array( $variation['id'], $used_ids, true ) ) {
				$variation['id'] = $original_id . '-' . $suffix;
				$suffix++;
			}

			$used_ids[] = $variation['id'];

			if ( $variation['is_default'] ) {
				if ( $has_default ) {
					$variation['is_default'] = 0;
				} else {
					$has_default = true;
				}
			}

			$sanitized[] = $variation;
		}

		if ( ! empty( $sanitized ) && ! $has_default ) {
			$sanitized[0]['is_default'] = 1;
		}

		return $sanitized;
	}

	/**
	 * Injects the variation parameter from a buy button's data-variation into submission data.
	 *
	 * @since 2.8.47
	 *
	 * @param array                          $data       Submission data.
	 * @param GetPaid_Payment_Form_Submission $submission Submission object.
	 * @return array
	 */
	public function inject_variation_from_request( $data, $submission ) {
		if ( empty( $data['getpaid-variation'] ) ) {
			$variation = isset( $_REQUEST['variation'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['variation'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! empty( $variation ) && ! empty( $data['getpaid-items'] ) && is_array( $data['getpaid-items'] ) ) {
				$item_ids = array_keys( $data['getpaid-items'] );

				if ( 1 === count( $item_ids ) ) {
					$data['getpaid-variation'] = array( $item_ids[0] => $variation );
				}
			}
		}

		return $data;
	}

	/**
	 * Applies the selected variation to the form item during checkout.
	 *
	 * @since 2.8.47
	 *
	 * @param GetPaid_Form_Item              $item       The form item.
	 * @param GetPaid_Payment_Form_Submission $submission The submission object.
	 * @return GetPaid_Form_Item
	 */
	public function apply_variation_to_item( $item, $submission ) {
		if ( ! self::item_has_variations( $item->get_id() ) ) {
			return $item;
		}

		$data       = $submission->get_data();
		$item_id    = $item->get_id();
		$variation_id = ! empty( $data['getpaid-variation'][ $item_id ] ) ? sanitize_text_field( $data['getpaid-variation'][ $item_id ] ) : '';
		$variation    = ! empty( $variation_id ) ? self::get_variation_by_id( $item_id, $variation_id ) : self::get_default_variation( $item_id );

		if ( empty( $variation ) ) {
			return $item;
		}

		/** @since 2.8.47 */
		$variation = apply_filters( 'getpaid_item_variation_before_apply', $variation, $item, $submission );

		if ( empty( $variation ) ) {
			return $item;
		}

		$item->set_price( $variation['price'] );

		if ( ! empty( $variation['is_recurring'] ) ) {
			$item->set_is_recurring( true );
			$item->set_recurring_period( $variation['recurring_period'] );
			$item->set_recurring_interval( $variation['recurring_interval'] );
			$item->set_recurring_limit( $variation['recurring_limit'] );
		} else {
			$item->set_is_recurring( false );
		}

		$meta                    = (array) $item->get_item_meta( 'edit' );
		$meta['variation_id']    = $variation['id'];
		$meta['variation_name']  = $variation['name'];
		$meta['variation_data']  = $variation;
		$item->set_item_meta( $meta );

		/** @since 2.8.47 */
		do_action( 'getpaid_item_variation_applied', $item, $variation, $submission );

		return $item;
	}

	/**
	 * Enqueues frontend CSS and JS globally for AJAX modal compatibility.
	 *
	 * @since 2.8.47
	 */
	public function enqueue_frontend_assets() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_style( 'getpaid-item-variations', WPINV_PLUGIN_URL . 'assets/css/getpaid-variations' . $suffix . '.css', array(), WPINV_VERSION );
	}

	/**
	 * Renders variation selectors for all items in a payment form.
	 *
	 * @since 2.8.47
	 *
	 * @param GetPaid_Payment_Form $form The payment form.
	 */
	public function render_variation_selectors( $form ) {
		$preselected = isset( $_REQUEST['variation'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['variation'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		foreach ( $form->get_items() as $item ) {
			$item_id  = $item->get_id();
			$variations = self::get_item_variations( $item_id );

			if ( empty( $variations ) ) {
				continue;
			}

			if ( ! empty( $preselected ) && self::get_variation_by_id( $item_id, $preselected ) ) {
				printf( '<input type="hidden" name="getpaid-variation[%s]" value="%s">', esc_attr( $item_id ), esc_attr( $preselected ) );
				continue;
			}

			$selected_id = $preselected;

			if ( empty( $selected_id ) ) {
				$default     = self::get_default_variation( $item_id );
				$selected_id = $default ? $default['id'] : '';
			}

			$currency = $form->get_currency();

			wpinv_get_template( 'payment-forms/variation-selector.php', compact( 'item_id', 'variations', 'selected_id', 'currency', 'item', 'form' ) );
		}
	}

	/**
	 * Displays the purchased variation name on frontend invoice line items.
	 *
	 * @since 2.8.47
	 *
	 * @param GetPaid_Form_Item $item    The line item.
	 * @param WPInv_Invoice     $invoice The invoice.
	 */
	public function display_line_item_variation( $item, $invoice ) {
		$variation_data = self::get_invoice_item_variation( $item );

		if ( $variation_data && ! empty( $variation_data['name'] ) ) {
			printf( '<small class="d-block text-muted">%s: %s</small>', esc_html__( 'Variation', 'invoicing' ), esc_html( $variation_data['name'] ) );
		}
	}

	/**
	 * Prepends the variation name to admin invoice line item summaries.
	 *
	 * @since 2.8.47
	 *
	 * @param string            $summary Existing summary.
	 * @param GetPaid_Form_Item $item    The line item.
	 * @param WPInv_Invoice     $invoice The invoice.
	 * @return string
	 */
	public function admin_line_item_variation( $summary, $item, $invoice ) {
		$variation_data = self::get_invoice_item_variation( $item );

		if ( $variation_data && ! empty( $variation_data['name'] ) ) {
			$badge   = sprintf( '<strong>%s</strong>', esc_html( $variation_data['name'] ) );
			$summary = $badge . ( $summary ? ' &mdash; ' . $summary : '' );
		}

		return $summary;
	}

	/**
	 * Renders per-variation buy shortcodes in the Item Info sidebar.
	 *
	 * @since 2.8.47
	 *
	 * @param WPInv_Item $item The item being edited.
	 */
	public function render_variation_shortcodes( $item ) {
		$variations = self::get_item_variations( $item->get_id() );

		if ( empty( $variations ) ) {
			return;
		}

		?>
		<div class="getpaid-variation-shortcodes form-group mb-3 row">
			<label class="col-sm-12 col-form-label">
				<?php esc_html_e( 'Variation Buy Links', 'invoicing' ); ?>
				<span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Use these to link directly to a specific variation.', 'invoicing' ); ?>"></span>
			</label>

			<div class="col-sm-12">
				<?php foreach ( $variations as $variation ) : ?>
					<div class="mb-2">
						<small class="d-block text-muted mb-1"><strong><?php echo esc_html( $variation['name'] ); ?></strong></small>
						<input onClick="this.select()" type="text" class="w-100" value="[getpaid item=<?php echo esc_attr( $item->get_id() ); ?> button='<?php echo esc_attr( $variation['name'] ); ?>' variation='<?php echo esc_attr( $variation['id'] ); ?>']" readonly />
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Sets the active variation context for an item during invoice processing.
	 *
	 * @since 2.8.47
	 *
	 * @param int   $item_id      Item post ID.
	 * @param array $variation_data Variation data.
	 */
	public static function set_active_variation( $item_id, $variation_data ) {
		self::$active_variations[ $item_id ] = $variation_data;
	}

	/**
	 * Gets the active variation context for an item.
	 *
	 * @since 2.8.47
	 *
	 * @param int $item_id Item post ID.
	 * @return array|false
	 */
	public static function get_active_variation( $item_id ) {
		return isset( self::$active_variations[ $item_id ] ) ? self::$active_variations[ $item_id ] : false;
	}

	/**
	 * Clears all active variation contexts.
	 *
	 * @since 2.8.47
	 */
	public static function clear_active_variations() {
		self::$active_variations = array();
	}

	/**
	 * Sets variation contexts from an invoice's items at priority 5.
	 *
	 * @since 2.8.47
	 *
	 * @param WPInv_Invoice $invoice The invoice.
	 */
	public function set_variation_contexts( $invoice ) {
		if ( ! $invoice->get_id() ) {
			return;
		}

		foreach ( $invoice->get_items() as $item ) {
			$variation_data = self::get_invoice_item_variation( $item );

			if ( $variation_data ) {
				self::set_active_variation( $item->get_id(), $variation_data );
			}
		}
	}

	/**
	 * Sets variation contexts from a subscription's parent invoice.
	 *
	 * @since 2.8.47
	 *
	 * @param WPInv_Subscription $subscription The subscription.
	 */
	public function set_subscription_variation_contexts( $subscription ) {
		$this->set_variation_contexts( $subscription->get_parent_payment() );
	}

	/**
	 * Intercepts get_post_meta to return variation overrides for license manager keys.
	 *
	 * @since 2.8.47
	 *
	 * @param mixed  $value     Current meta value.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Single value flag.
	 * @return mixed
	 */
	public function filter_license_meta( $value, $object_id, $meta_key, $single ) {
		if ( empty( self::$active_variations ) || ! isset( self::$license_meta_map[ $meta_key ] ) ) {
			return $value;
		}

		$variation_data = self::get_active_variation( $object_id );

		if ( ! $variation_data ) {
			return $value;
		}

		$key = self::$license_meta_map[ $meta_key ];

		if ( isset( $variation_data[ $key ] ) && '' !== $variation_data[ $key ] ) {
			return $single ? $variation_data[ $key ] : array( $variation_data[ $key ] );
		}

		return $value;
	}

	/**
	 * Filters item downloads to only include files assigned to the purchased variation.
	 *
	 * @since 2.8.47
	 *
	 * @param GetPaid_Item_Download[] $downloads Item downloads.
	 * @param int                     $item_id   Item post ID.
	 * @return GetPaid_Item_Download[]
	 */
	public function filter_item_downloads( $downloads, $item_id ) {
		$variation_data = self::get_active_variation( $item_id );

		if ( ! $variation_data || empty( $variation_data['download_ids'] ) ) {
			return $downloads;
		}

		$allowed_ids = array_map( 'absint', (array) $variation_data['download_ids'] );

		return array_filter(
			$downloads,
			function ( $download ) use ( $allowed_ids ) {
				return in_array( (int) $download->get_id(), $allowed_ids, true );
			}
		);
	}
}
