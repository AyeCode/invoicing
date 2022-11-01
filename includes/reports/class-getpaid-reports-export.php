<?php
/**
 * Contains the class that displays the reports export page.
 *
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Reports_Export Class.
 */
class GetPaid_Reports_Export {

	/**
	 * Displays the reports tab.
	 *
	 */
	public function display() {

		echo "<div class='row mt-4' style='max-width: 920px;' >";
		foreach ( array_keys( getpaid_get_invoice_post_types() ) as $post_type ) {
			$this->display_post_type_export( $post_type );
		}
		$this->display_subscription_export();
		echo '</div>';

	}

	/**
	 * Retrieves the download url.
	 *
	 */
	public function get_download_url( $post_type ) {

		return wp_nonce_url(
			add_query_arg(
				array(
					'getpaid-admin-action' => 'export_invoices',
					'post_type'            => urlencode( $post_type ),
				)
			),
			'getpaid-nonce',
			'getpaid-nonce'
		);

	}

	/**
	 * Displays a single post type export card.
	 *
	 */
	public function display_post_type_export( $post_type ) {

		?>

		<div class="col-12 col-md-6">
			<div class="card m-0 p-0" style="max-width:100%">

				<div class="card-header">
					<strong>
						<?php
							printf(
								esc_html__( 'Export %s', 'invoicing' ),
								esc_html( getpaid_get_post_type_label( $post_type ) )
							);
						?>
					</strong>
				</div>

				<div class="card-body">

					<form method="post" action="<?php echo esc_url( $this->get_download_url( $post_type ) ); ?>">

						<?php
							$this->generate_from_date( $post_type );
							$this->generate_to_date( $post_type );
							$this->generate_post_status_select( $post_type );
							$this->generate_file_type_select( $post_type );
							submit_button( __( 'Download', 'invoicing' ) );
						?>

					</form>

				</div>

			</div>
		</div>

		<?php

	}

	/**
	 * Generates the from date input field.
	 *
	 */
	public function generate_from_date( $post_type ) {

		aui()->input(
			array(
				'type'             => 'datepicker',
				'id'               => esc_attr( "$post_type-from_date" ),
				'name'             => 'from_date',
				'label'            => __( 'From Date', 'invoicing' ),
				'label_type'       => 'vertical',
				'placeholder'      => 'YYYY-MM-DD',
				'extra_attributes' => array(
					'data-enable-time' => 'false',
					'data-allow-input' => 'true',
				),
			),
			true
		);

	}

	/**
	 * Generates the to date input field.
	 *
	 */
	public function generate_to_date( $post_type ) {

		aui()->input(
			array(
				'type'             => 'datepicker',
				'id'               => esc_attr( "$post_type-to_date" ),
				'name'             => 'to_date',
				'label'            => __( 'To Date', 'invoicing' ),
				'label_type'       => 'vertical',
				'placeholder'      => 'YYYY-MM-DD',
				'extra_attributes' => array(
					'data-enable-time' => 'false',
					'data-allow-input' => 'true',
				),
			),
			true
		);
	}

	/**
	 * Generates the to post status select field.
	 *
	 */
	public function generate_post_status_select( $post_type ) {

		if ( 'subscriptions' === $post_type ) {
			$options = getpaid_get_subscription_statuses();
		} else {
			$options = wpinv_get_invoice_statuses( true, false, $post_type );
		}

		aui()->select(
			array(
				'name'        => 'status',
				'id'          => esc_attr( "$post_type-status" ),
				'placeholder' => __( 'All Statuses', 'invoicing' ),
				'label'       => __( 'Status', 'invoicing' ),
				'label_type'  => 'vertical',
				'label_class' => 'd-block',
				'options'     => $options,
			),
			true
		);

	}

	/**
	 * Generates the to file type select field.
	 *
	 */
	public function generate_file_type_select( $post_type ) {

		aui()->select(
			array(
				'name'        => 'file_type',
				'id'          => esc_attr( "$post_type-file_type" ),
				'placeholder' => __( 'Select File Type', 'invoicing' ),
				'label'       => __( 'Export File', 'invoicing' ),
				'label_type'  => 'vertical',
				'label_class' => 'd-block',
				'value'       => 'csv',
				'options'     => array(
					'csv'  => __( 'CSV', 'invoicing' ),
					'xml'  => __( 'XML', 'invoicing' ),
					'json' => __( 'JSON', 'invoicing' ),
				),
			),
			true
		);

	}

	/**
	 * Displays a field's markup.
	 *
	 */
	public function display_markup( $markup ) {

		echo wp_kses(
			str_replace(
				array(
					'form-control',
					'custom-select',
				),
				'regular-text',
				$markup
			),
			getpaid_allowed_html()
		);

	}

	/**
	 * Displays a subscription export card.
	 *
	 */
	public function display_subscription_export() {

		?>

		<div class="col-12 col-md-6">
			<div class="card m-0 p-0" style="max-width:100%">

				<div class="card-header">
					<strong>
						<?php esc_html_e( 'Export Subscriptions', 'invoicing' ); ?>
					</strong>
				</div>

				<div class="card-body">

					<form method="post" action="<?php echo esc_url( $this->get_download_url( 'subscriptions' ) ); ?>">

						<?php
							$this->generate_from_date( 'subscriptions' );
							$this->generate_to_date( 'subscriptions' );
							$this->generate_post_status_select( 'subscriptions' );
							$this->generate_file_type_select( 'subscriptions' );
							submit_button( __( 'Download', 'invoicing' ) );
						?>

					</form>

				</div>

			</div>
		</div>

		<?php

	}

}
