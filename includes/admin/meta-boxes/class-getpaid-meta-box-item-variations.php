<?php
/**
 * Item Variations Meta Box.
 *
 * @version 2.8.47
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the variations section on the item edit screen.
 *
 * @since 2.8.47
 */
class GetPaid_Meta_Box_Item_Variations {

	/**
	 * Enqueues admin scripts and styles on item edit screens.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public static function enqueue_scripts( $hook_suffix ) {
		global $post_type;

		if ( 'wpi_item' !== $post_type || ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'getpaid-item-variations-admin', WPINV_PLUGIN_URL . 'assets/js/item-variations' . $suffix . '.js', array( 'jquery', 'jquery-ui-sortable' ), WPINV_VERSION, true );

		wp_localize_script(
			'getpaid-item-variations-admin',
			'getpaidItemVariations',
			apply_filters(
				'getpaid_item_variations_admin_js_data',
				array(
					'i18n' => array(
						'confirmRemove' => __( 'Remove this variation?', 'invoicing' ),
						'confirmDelete' => __( 'Delete', 'invoicing' ),
						'confirmCancel' => __( 'Cancel', 'invoicing' ),
						'nameRequired'  => __( 'Each variation must have a name.', 'invoicing' ),
					),
				)
			)
		);
	}

	/**
	 * Outputs the variations section inside the item details meta box.
	 *
	 * @param WPInv_Item $item The item being edited.
	 */
	public static function output( $item ) {
		$has_variations = (int) get_post_meta( $item->get_id(), '_wpinv_has_variations', true );
		$variations     = GetPaid_Item_Variations::get_item_variations( $item->get_id() );

		?>

		<div class="bsui getpaid-item-variations-wrap">

			<div class="form-group mb-3 row">
				<label class="col-sm-3 col-form-label">
					<?php esc_html_e( 'Variable Pricing', 'invoicing' ); ?>
				</label>
				<div class="col-sm-8 pt-2">
					<?php
					aui()->input(
						array(
							'id'      => '_wpinv_has_variations',
							'name'    => '_wpinv_has_variations',
							'type'    => 'checkbox',
							'label'   => __( 'Enable pricing variations', 'invoicing' ),
							'value'   => '1',
							'checked' => ! empty( $has_variations ),
							'no_wrap' => true,
							'switch'  => 'sm',
						),
						true
					);
					?>
				</div>
				<div class="col-sm-1 pt-2 pl-0">
					<span class="wpi-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Sell multiple pricing tiers (e.g. Personal, Business, Agency) from a single item. Each variation can have its own price, billing cycle, activation limits, and download files.', 'invoicing' ); ?>"></span>
				</div>
			</div>

			<div class="getpaid-variations-box <?php echo empty( $has_variations ) ? 'collapse' : ''; ?>">

				<div id="getpaid-variations-list">
					<?php
					if ( ! empty( $variations ) ) {
						foreach ( $variations as $index => $variation ) {
							self::render_variation_card( $index, $variation );
						}
					}
					?>
				</div>

				<div id="getpaid-variations-empty" class="text-center text-muted py-4 border rounded <?php echo ! empty( $variations ) ? 'd-none' : ''; ?>" style="border-style: dashed !important;">
					<?php esc_html_e( 'No variations defined. Add your first pricing tier.', 'invoicing' ); ?>
				</div>

				<p class="mt-3 mb-0">
					<button type="button" class="button button-secondary" id="getpaid-add-variation">
						<?php esc_html_e( '+ Add Variation', 'invoicing' ); ?>
					</button>
				</p>

			</div>

		</div>

		<script type="text/html" id="getpaid-variation-card-template">
			<?php self::render_variation_card( '__INDEX__', array() ); ?>
		</script>

		<?php
	}

	/**
	 * Renders a single variation card.
	 *
	 * @param int|string $index     Card index.
	 * @param array      $variation Variation data.
	 */
	public static function render_variation_card( $index, $variation ) {
		$variation = wp_parse_args(
			$variation,
			array(
				'id'                 => '',
				'name'               => '',
				'price'              => '',
				'is_recurring'       => 0,
				'recurring_period'   => 'Y',
				'recurring_interval' => 1,
				'recurring_limit'    => 0,
				'is_default'         => 0,
				'max_activations'    => '',
				'license_prefix'     => '',
				'license_interval'   => '',
				'license_period'     => 'years',
				'download_ids'       => array(),
			)
		);

		$n        = "_wpinv_item_variations[{$index}]";
		$position = str_replace( '_space', '', wpinv_currency_position() );
		?>
		<div class="getpaid-variation-card border rounded mb-3 bg-white" data-index="<?php echo esc_attr( $index ); ?>">

			<input type="hidden" name="<?php echo esc_attr( $n ); ?>[id]" value="<?php echo esc_attr( $variation['id'] ); ?>" class="getpaid-pkg-id">

			<div class="getpaid-pkg-header d-flex align-items-center gap-2 p-2 border-bottom bg-light rounded-top">
				<span class="dashicons dashicons-move getpaid-pkg-sort-handle"></span>

				<label class="d-flex align-items-center gap-1 mb-0 flex-shrink-0 text-muted c-pointer" title="<?php esc_attr_e( 'Pre-selected when no variation is specified', 'invoicing' ); ?>">
					<input type="radio" name="_wpinv_variation_default" value="<?php echo esc_attr( $index ); ?>" <?php checked( $variation['is_default'] ); ?>>
					<?php esc_html_e( 'Default', 'invoicing' ); ?>
				</label>

				<input type="text" name="<?php echo esc_attr( $n ); ?>[name]" value="<?php echo esc_attr( $variation['name'] ); ?>" class="form-control form-control-sm getpaid-pkg-name flex-fill" placeholder="<?php esc_attr_e( 'Variation name, e.g. Personal, Business, Agency', 'invoicing' ); ?>">

				<div class="input-group input-group-sm getpaid-pkg-price-wrap flex-shrink-0">
					<?php if ( 'left' === $position ) : ?>
						<span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
					<?php endif; ?>
					<input type="text" name="<?php echo esc_attr( $n ); ?>[price]" value="<?php echo esc_attr( '' !== $variation['price'] ? getpaid_unstandardize_amount( $variation['price'] ) : '' ); ?>" class="form-control getpaid-force-integer<?php echo ( wpinv_decimal_separator() === ',' ? ' getpaid-force-comma' : '' ); ?>" placeholder="<?php echo esc_attr( wpinv_sanitize_amount( 0 ) ); ?>">
					<?php if ( 'left' !== $position ) : ?>
						<span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
					<?php endif; ?>
				</div>

				<button type="button" class="getpaid-remove-variation flex-shrink-0" title="<?php esc_attr_e( 'Remove variation', 'invoicing' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>

			<div class="getpaid-pkg-body px-3 py-2">

				<div class="getpaid-pkg-section d-flex flex-wrap align-items-center gap-1 py-2">
					<label class="getpaid-pkg-toggle-label d-flex align-items-center gap-1 mb-0">
						<input type="checkbox" name="<?php echo esc_attr( $n ); ?>[is_recurring]" value="1" <?php checked( $variation['is_recurring'] ); ?> class="getpaid-pkg-recurring-toggle">
						<?php esc_html_e( 'Recurring', 'invoicing' ); ?>
					</label>

					<span class="getpaid-pkg-recurring-fields d-inline-flex align-items-center flex-wrap gap-1 <?php echo empty( $variation['is_recurring'] ) ? 'd-none' : ''; ?>">
						&mdash;
						<?php esc_html_e( 'every', 'invoicing' ); ?>
						<input type="number" name="<?php echo esc_attr( $n ); ?>[recurring_interval]" value="<?php echo esc_attr( $variation['recurring_interval'] ); ?>" class="form-control form-control-sm getpaid-pkg-sm-input" min="1">
						<select name="<?php echo esc_attr( $n ); ?>[recurring_period]" class="form-select form-select-sm w-auto">
							<option value="D" <?php selected( 'D', $variation['recurring_period'] ); ?>><?php esc_html_e( 'day(s)', 'invoicing' ); ?></option>
							<option value="W" <?php selected( 'W', $variation['recurring_period'] ); ?>><?php esc_html_e( 'week(s)', 'invoicing' ); ?></option>
							<option value="M" <?php selected( 'M', $variation['recurring_period'] ); ?>><?php esc_html_e( 'month(s)', 'invoicing' ); ?></option>
							<option value="Y" <?php selected( 'Y', $variation['recurring_period'] ); ?>><?php esc_html_e( 'year(s)', 'invoicing' ); ?></option>
						</select>
					</span>
				</div>

				<?php do_action( 'getpaid_variation_card_after_billing', $index, $variation, $n ); ?>

			</div>

		</div>
		<?php
	}

	/**
	 * Saves the variations meta box data.
	 *
	 * @param int        $post_id The item post ID.
	 * @param WPInv_Item $item    The item being saved.
	 */
	public static function save( $post_id, $item ) {
		$data           = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$has_variations = ! empty( $data['_wpinv_has_variations'] ) ? 1 : 0;
		$variations     = array();

		if ( $has_variations && ! empty( $data['_wpinv_item_variations'] ) && is_array( $data['_wpinv_item_variations'] ) ) {
			$default_index = isset( $data['_wpinv_variation_default'] ) ? $data['_wpinv_variation_default'] : 0;

			foreach ( $data['_wpinv_item_variations'] as $index => $raw ) {
				if ( ! is_array( $raw ) ) {
					continue;
				}

				if ( empty( $raw['id'] ) && ! empty( $raw['name'] ) ) {
					$raw['id'] = sanitize_title( $raw['name'] );
				}

				if ( isset( $raw['price'] ) ) {
					$raw['price'] = wpinv_sanitize_amount( $raw['price'] );
				}

				$raw['is_default'] = ( (string) $index === (string) $default_index ) ? 1 : 0;
				$variations[]      = $raw;
			}

			$variations = GetPaid_Item_Variations::sanitize_variations( $variations );
		}

		if ( $has_variations && empty( $variations ) ) {
			$has_variations = 0;
		}

		/**
		 * Filters the sanitized variations before saving.
		 *
		 * @since 2.8.47
		 *
		 * @param array $variations     Sanitized variations.
		 * @param int   $post_id        Item post ID.
		 * @param int   $has_variations Whether variations are enabled (1 or 0).
		 */
		$variations = apply_filters( 'getpaid_item_variations_before_save', $variations, $post_id, $has_variations );

		update_post_meta( $post_id, '_wpinv_has_variations', $has_variations );
		update_post_meta( $post_id, '_wpinv_item_variations', $variations );
	}
}
