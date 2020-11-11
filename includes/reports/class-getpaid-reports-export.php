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
		echo "</div>";

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
								__( 'Export %s', 'invoicing' ),
								sanitize_text_field( getpaid_get_post_type_label( $post_type ) )
							);
						?>
					</strong>
				</div>

				<div class="card-body">

					<form method="post" action="<?php echo esc_url( $this->get_download_url( $post_type ) ); ?>">

						<?php
							$this->display_markup( $this->generate_from_date( $post_type ) );
							$this->display_markup( $this->generate_to_date( $post_type ) );
							$this->display_markup( $this->generate_post_status_select( $post_type ) );
							$this->display_markup( $this->generate_file_type_select( $post_type ) );
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

		return aui()->input(
			array(
				'name'       => 'from_date',
				'id'         => esc_attr( "$post_type-from_date" ),
				'placeholder'=> 'yy-mm-dd',
				'label'      => __( 'From Date', 'invoicing' ),
				'label_type' => 'vertical',
				'label_class' => 'd-block',
				'type'       => 'datepicker',
			)
		);

	}

	/**
	 * Generates the to date input field.
	 *
	 */
	public function generate_to_date( $post_type ) {

		return aui()->input(
			array(
				'name'       => 'to_date',
				'id'         => esc_attr( "$post_type-to_date" ),
				'placeholder'=> 'yy-mm-dd',
				'label'      => __( 'To Date', 'invoicing' ),
				'label_type' => 'vertical',
				'label_class' => 'd-block',
				'type'       => 'datepicker',
			)
		);

	}

	/**
	 * Generates the to post status select field.
	 *
	 */
	public function generate_post_status_select( $post_type ) {

		return aui()->select(
			array(
				'name'        => 'status',
				'id'          => esc_attr( "$post_type-status" ),
				'placeholder' => __( 'All Statuses', 'invoicing' ),
				'label'       => __( 'Status', 'invoicing' ),
				'label_type'  => 'vertical',
				'label_class' => 'd-block',
				'options'     => wpinv_get_invoice_statuses( true, false, $post_type ),
			)
		);

	}

	/**
	 * Generates the to file type select field.
	 *
	 */
	public function generate_file_type_select( $post_type ) {

		return aui()->select(
			array(
				'name'        => 'file_type',
				'id'          => esc_attr( "$post_type-file_type" ),
				'placeholder' => __( 'Select File Type', 'invoicing' ),
				'label'       => __( 'Export File', 'invoicing' ),
				'label_type'  => 'vertical',
				'label_class' => 'd-block',
				'options'     => array(
					'csv'  => __( 'CSV', 'invoicing' ),
					'xml'  => __( 'XML', 'invoicing' ),
					'json' => __( 'JSON', 'invoicing' ),
				),
			)
		);

	}

	/**
	 * Displays a field's markup.
	 *
	 */
	public function display_markup( $markup ) {

		echo str_replace(
			array(
				'form-control',
				'custom-select'
			),
			'regular-text',
			$markup
		);

	}

}
