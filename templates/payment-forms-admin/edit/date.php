<?php
/**
 * Displays a date input setting in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/edit/date.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div class='form-group'>
	<label class="d-block">
		<span><?php esc_html_e( 'Field Label', 'invoicing' ); ?></span>
		<input v-model='active_form_element.label' class='form-control' type="text"/>
	</label>
</div>

<div class='form-group'>
	<label class="d-block">
		<span><?php esc_html_e( 'Default Date', 'invoicing' ); ?></span>
		<?php echo wpi_help_tip( sprintf( __( 'You can enter the shortcut "today" or enter a date matching the format Y-m-d, e.g %s', 'invoicing' ), current_time( 'Y-m-d' ) ), false, true ); ?>
		<input v-model='active_form_element.default_date' placeholder="<?php esc_attr_e( 'None', 'invoicing' ); ?>" class='form-control' type="text"/>
	</label>
</div>

<div class='form-group'>
	<label class="d-block">
		<span><?php esc_html_e( 'Minimum Date', 'invoicing' ); ?></span>
		<?php echo wpi_help_tip( sprintf( __( 'You can enter the shortcut "today" or enter a date matching the format Y-m-d, e.g %s', 'invoicing' ), current_time( 'Y-m-d' ) ), false, true ); ?>
		<input v-model='active_form_element.min_date' placeholder="<?php esc_attr_e( 'None', 'invoicing' ); ?>" class='form-control' type="text"/>
		<small class="form-text text-muted"><?php _e( 'Specify the minimum/earliest date (inclusively) allowed for selection.', 'invoicing' ); ?></small>
	</label>
</div>

<div class='form-group'>
	<label class="d-block">
		<span><?php esc_html_e( 'Maximum Date', 'invoicing' ); ?></span>
		<?php echo wpi_help_tip( sprintf( __( 'You can enter the shortcut "today" or enter a date matching the format Y-m-d, e.g %s', 'invoicing' ), current_time( 'Y-m-d' ) ), false, true ); ?>
		<input v-model='active_form_element.max_date' placeholder="<?php esc_attr_e( 'None', 'invoicing' ); ?>" class='form-control' type="text"/>
		<small class="form-text text-muted"><?php _e( 'Specify the maximum/latest date (inclusively) allowed for selection.', 'invoicing' ); ?></small>
	</label>
</div>

<div class='form-group'>
	<label class="d-block">
		<span><?php esc_html_e( 'Mode', 'invoicing' ) ?></span>
		<select class='form-control custom-select' v-model='active_form_element.mode'>
			<option value='single'><?php esc_html_e( 'Users can only select a single date', 'invoicing' ); ?></option>
			<option value='range'><?php esc_html_e( 'Users can select a date range', 'invoicing' ); ?></option>
			<option value='multiple'><?php esc_html_e( 'Users can select multiple dates', 'invoicing' ); ?></option>
		</select>
	</label>
</div>

<div class='form-group'>
	<label class="d-block">
		<span><?php esc_html_e( 'Help Text', 'invoicing' ); ?></span>
		<textarea placeholder='<?php esc_attr_e( 'Add some help text for this field', 'invoicing' ); ?>' v-model='active_form_element.description' class='form-control' rows='3'></textarea>
		<small class="form-text text-muted"><?php _e( 'HTML is allowed', 'invoicing' ); ?></small>
	</label>
</div>

<div class='form-group form-check'>
	<input :id="active_form_element.id + '_edit'" v-model='active_form_element.required' type='checkbox' class='form-check-input' />
	<label class='form-check-label' :for="active_form_element.id + '_edit'"><?php esc_html_e( 'Is this field required?', 'invoicing' ); ?></label>
</div>

<div class='form-group form-check'>
	<input :id="active_form_element.id + '_add_meta'" v-model='active_form_element.add_meta' type='checkbox' class='form-check-input' />
	<label class='form-check-label' :for="active_form_element.id + '_add_meta'"><?php esc_html_e( 'Show this field in receipts and emails?', 'invoicing' ); ?></label>
</div>

<hr class='featurette-divider mt-4'>

<div class='form-group'>
	<label class="d-block">
		<span><?php esc_html_e( 'Email Merge Tag', 'invoicing' ); ?></span>
		<input :value='active_form_element.label | formatMergeTag' class='form-control bg-white' type="text" readonly onclick="this.select()" />
		<span class="form-text text-muted"><?php esc_html_e( 'You can use this merge tag in notification emails', 'invoicing' ); ?></span>
	</label>
</div>

<hr class='featurette-divider mt-4'>
