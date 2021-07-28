<?php
/**
 * Add extra profile fields for users in admin
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'GetPaid_Admin_Profile', false ) ) :

	/**
	 * GetPaid_Admin_Profile Class.
	 */
	class GetPaid_Admin_Profile {

		/**
		 * Hook in tabs.
		 */
		public function __construct() {
			add_action( 'show_user_profile', array( $this, 'add_customer_meta_fields' ), 100 );
			add_action( 'edit_user_profile', array( $this, 'add_customer_meta_fields' ), 100 );

			add_action( 'personal_options_update', array( $this, 'save_customer_meta_fields' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_customer_meta_fields' ) );
		}

		/**
		 * Get Address Fields for the edit user pages.
		 *
		 * @return array Fields to display which are filtered through invoicing_customer_meta_fields before being returned
		 */
		public function get_customer_meta_fields() {

			$show_fields = apply_filters(
				'getpaid_customer_meta_fields',
				array(
					'billing'  => array(
						'title'  => __( 'Billing Details (GetPaid)', 'invoicing' ),
						'fields' => array(
							'_wpinv_first_name' => array(
								'label'       => __( 'First name', 'invoicing' ),
								'description' => '',
							),
							'_wpinv_last_name'  => array(
								'label'       => __( 'Last name', 'invoicing' ),
								'description' => '',
							),
							'_wpinv_company'    => array(
								'label'       => __( 'Company', 'invoicing' ),
								'description' => '',
							),
							'_wpinv_company_id'    => array(
								'label'       => __( 'Company ID', 'invoicing' ),
								'description' => '',
							),
							'_wpinv_address'  => array(
								'label'       => __( 'Address', 'invoicing' ),
								'description' => '',
							),
							'_wpinv_city'       => array(
								'label'       => __( 'City', 'invoicing' ),
								'description' => '',
							),
							'_wpinv_zip'   => array(
								'label'       => __( 'Postcode / ZIP', 'invoicing' ),
								'description' => '',
							),
							'_wpinv_country'    => array(
								'label'       => __( 'Country / Region', 'invoicing' ),
								'description' => '',
								'class'       => 'getpaid_js_field-country',
								'type'        => 'select',
								'options'     => array( '' => __( 'Select a country / region&hellip;', 'invoicing' ) ) + wpinv_get_country_list(),
							),
							'_wpinv_state'      => array(
								'label'       => __( 'State / County', 'invoicing' ),
								'description' => __( 'State / County or state code', 'invoicing' ),
								'class'       => 'getpaid_js_field-state regular-text',
							),
							'_wpinv_phone'      => array(
								'label'       => __( 'Phone', 'invoicing' ),
								'description' => '',
							),
							'_wpinv_vat_number'      => array(
								'label'       => __( 'VAT Number', 'invoicing' ),
								'description' => '',
							),
						),
					),
				)
			);
			return $show_fields;
		}

		/**
		 * Show Address Fields on edit user pages.
		 *
		 * @param WP_User $user
		 */
		public function add_customer_meta_fields( $user ) {

			if ( ! apply_filters( 'getpaid_current_user_can_edit_customer_meta_fields', current_user_can( 'manage_options' ), $user->ID ) ) {
				return;
			}

			$show_fields = $this->get_customer_meta_fields();

			foreach ( $show_fields as $fieldset_key => $fieldset ) :
				?>
				<h2><?php echo $fieldset['title']; ?></h2>
				<table class="form-table" id="<?php echo esc_attr( 'getpaid-fieldset-' . $fieldset_key ); ?>">
					<?php foreach ( $fieldset['fields'] as $key => $field ) : ?>
						<tr>
							<th>
								<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
							</th>
							<td>
								<?php if ( ! empty( $field['type'] ) && 'select' === $field['type'] ) : ?>
									<select name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="<?php echo esc_attr( $field['class'] ); ?> wpi_select2" style="width: 25em;">
										<?php
											$selected = esc_attr( get_user_meta( $user->ID, $key, true ) );
										foreach ( $field['options'] as $option_key => $option_value ) :
											?>
											<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $selected, $option_key, true ); ?>><?php echo esc_html( $option_value ); ?></option>
										<?php endforeach; ?>
									</select>
								<?php elseif ( ! empty( $field['type'] ) && 'checkbox' === $field['type'] ) : ?>
									<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="1" class="<?php echo esc_attr( $field['class'] ); ?>" <?php checked( (int) get_user_meta( $user->ID, $key, true ), 1, true ); ?> />
								<?php else : ?>
									<input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $this->get_user_meta( $user->ID, $key ) ); ?>" class="<?php echo ( ! empty( $field['class'] ) ? esc_attr( $field['class'] ) : 'regular-text' ); ?>" />
								<?php endif; ?>
								<p class="description"><?php echo wp_kses_post( $field['description'] ); ?></p>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php
			endforeach;
		}

		/**
		 * Save Address Fields on edit user pages.
		 *
		 * @param int $user_id User ID of the user being saved
		 */
		public function save_customer_meta_fields( $user_id ) {
			if ( ! apply_filters( 'getpaid_current_user_can_edit_customer_meta_fields', current_user_can( 'manage_options' ), $user_id ) ) {
				return;
			}

			$save_fields = $this->get_customer_meta_fields();

			foreach ( $save_fields as $fieldset ) {

				foreach ( $fieldset['fields'] as $key => $field ) {

					if ( isset( $field['type'] ) && 'checkbox' === $field['type'] ) {
						update_user_meta( $user_id, $key, isset( $_POST[ $key ] ) );
					} elseif ( isset( $_POST[ $key ] ) ) {
						update_user_meta( $user_id, $key, wpinv_clean( $_POST[ $key ] ) );
					}
				}
			}
		}

		/**
		 * Get user meta for a given key, with fallbacks to core user info for pre-existing fields.
		 *
		 * @since 3.1.0
		 * @param int    $user_id User ID of the user being edited
		 * @param string $key     Key for user meta field
		 * @return string
		 */
		protected function get_user_meta( $user_id, $key ) {
			$value           = get_user_meta( $user_id, $key, true );
			$existing_fields = array( '_wpinv_first_name', '_wpinv_last_name' );
			if ( ! $value && in_array( $key, $existing_fields ) ) {
				$value = get_user_meta( $user_id, str_replace( '_wpinv_', '', $key ), true );
			}

			return $value;
		}
	}

endif;

return new GetPaid_Admin_Profile();