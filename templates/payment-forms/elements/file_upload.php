<?php
/**
 * Displays a file_upload input in a payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/file_upload.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$label        = empty( $label ) ? '' : wp_kses_post( $label );
$label_class  = sanitize_key( preg_replace( '/[^A-Za-z0-9_-]/', '-', $label ) );
$id           = esc_attr( $id );
$_id          = $id . uniqid( '_' );
$max_file_num = empty( $max_file_num ) ? 1 : absint( $max_file_num );
$file_types   = empty( $file_types ) ? array( 'jpg|jpeg|jpe', 'gif', 'png' ) : $file_types;
$all_types    = getpaid_get_allowed_mime_types();
$types        = array();
$_types       = array();

foreach ( $file_types as $file_type ) {

	if ( isset( $all_types[ $file_type ] ) ) {
		$types[]   = $all_types[ $file_type ];
		$file_type = explode( '|', $file_type );

		foreach ( $file_type as $type ) {
			$type     = trim( $type );
			$types[]  = ".$type";
			$_types[] = $type;
		}

	}

}

if ( ! empty( $required ) ) {
	$label .= "<span class='text-danger'> *</span>";
}

?>

<label><span v-html="form_element.label"></span></label>

<div class="form-group <?php echo sanitize_html_class( $label_class ); ?>" data-name="<?php echo esc_attr( $id ); ?>" data-max="<?php echo esc_attr( $max_file_num ); ?>">
	<label for="<?php echo esc_attr( $id ); ?>"><?php echo wp_kses_post( $label ); ?></label>
	<input type="file" class="sr-only getpaid-files-input" id="<?php echo esc_attr( $id ); ?>" accept="<?php echo esc_attr( implode( ', ', $types ) ); ?>" data-extensions="<?php echo esc_attr( wp_json_encode( $_types ) ); ?>" <?php echo $max_file_num == 1 ? '' : 'multiple="multiple"'; ?>>

	<label for="<?php echo esc_attr( $id ); ?>" class="getpaid-file-upload-element d-flex w-100 flex-column align-items-center justify-content-center p-2 mb-2">
		<div class="h5 text-dark">
			<?php echo _n( 'Drag your file to this area or click to upload', 'Drag files to this area or click to upload', $max_file_num, 'invoicing' ); ?>
		</div>
		<?php if ( ! empty( $description ) ) : ?>
			<small class="form-text text-muted"><?php echo wp_kses_post( $description ); ?></small>
		<?php endif; ?>
	</label>

	<div class="getpaid-uploaded-files"></div>

	<div class="form-row mb-3 d-none getpaid-progress-template">

		<div class="overflow-hidden text-nowrap col-7 col-sm-4">
			<a href="" class="close float-none" title="<?php esc_attr_e( 'Remove File', 'invoicing' ); ?>">&times;<span class="sr-only"><?php _e( 'Close', 'invoicing' ); ?></span></a>&nbsp;
			<i class="fa fa-file" aria-hidden="true"></i>&nbsp; <span class="getpaid-progress-file-name"></span>&nbsp;
		</div>

		<div class="col-5 col-sm-8 getpaid-progress">
			<div class="progress" style="height: 40px">
				<div class="progress-bar" role="progressbar" style="width: 0" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
			</div>
		</div>

	</div>

</div>
