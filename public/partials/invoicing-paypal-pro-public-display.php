<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Invoicing_Paypal_Pro
 * @subpackage Invoicing_Paypal_Pro/public/partials
 */
$invoice = wpinv_get_invoice( $invoice_id );
$cc_owner = !empty( $invoice ) ? esc_attr( $invoice->get_user_full_name() ) : '';
$recuring = esc_attr(get_post_meta($invoice_id, 'paypalpro_rec_enable', TRUE));
$period = esc_attr(get_post_meta($invoice_id, 'paypalpro_rec_period', TRUE));
$frequency = esc_attr(get_post_meta($invoice_id, 'paypalpro_rec_frequency', TRUE));
?>

<!-- This file should primarily consist of HTML with a little bit of PHP -->
<?php if($recuring == 'Y'){ ?><div class="alert alert-info"><?php echo sprintf(__("it will be a recurring payment for %s %ss", 'invoicing'), $frequency, $period); ?></div><?php } ?>
<div class="card-payment form-horizontal wpi-cc-form panel panel-default">
    <div id="paymentSection" class="panel-body">
              <input type="hidden" name="paypalpro[card_type]" id="card_type" value=""/>
              <div class="form-group required">
                  <label class="col-sm-4" for="card_number"><?php _e('Card number', 'invoicing') ?> </label>
                  <div class="col-sm-8"><input type="text" placeholder="1234 5678 9012 3456" id="card_number" name="paypalpro[cc_number]" class=""></div>
              </div>
              <div class="vertical">
                      <div class="form-group required">
                          <label class="col-sm-4" for="expiry_month"><?php _e('Expiry month', 'invoicing'); ?></label>
                          <div class="col-sm-8"><input type="text" placeholder="MM" maxlength="5" id="expiry_month" name="paypalpro[cc_expire_month]"></div>
                      </div>
                      <div class="form-group required">
                          <label class="col-sm-4" for="expiry_year"><?php _e('Expiry year', 'invoicing'); ?></label>
                          <div class="col-sm-8"><input type="text" placeholder="YYYY" maxlength="5" id="expiry_year" name="paypalpro[cc_expire_year]"></div>
                      </div>
                      <div class="form-group required">
                          <label class="col-sm-4" for="cvv"><?php _e('CVV', 'invoicing'); ?></label>
                          <div class="col-sm-8"><input type="text" placeholder="123" maxlength="3" id="cvv" name="paypalpro[cc_cvv2]"></div>
                      </div>
                  <p class="alert alert-info"><?php _e('Name on card should match billing details above!', 'invoicing'); ?></p>
              </div>
  </div>
    <div id="orderInfo" style="display: none;"></div>
</div>
<div id="orderInfo" style="display: none;"></div>