<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!($user_id = get_current_user_id())) {
    ?>
    <div class="wpinv-empty alert alert-error"><?php _e('You are not allowed to access this section.', 'invoicing'); ?></div>
    <?php
    return;
}

global $wpdb;

$db = new WPInv_Subscriptions_DB;
$page = isset($_GET['cpage']) ? abs((int)$_GET['cpage']) : 1;
$items_per_page = get_option('posts_per_page');
$offset = ($page * $items_per_page) - $items_per_page;
$args = array('customer_id' => $user_id, 'offset' => $offset);
$total = $db->count($args);
$totalPage = ceil($total / $items_per_page);

$subs = $db->get_subscriptions($args);
?>
<?php
do_action('wpinv_subscriptions_front_notices');
do_action('wpinv_before_user_subscriptions', $subs);
if ($subs) { ?>
    <table class="table table-bordered table-hover wpi-user-subscriptions">
        <thead>
        <tr>
            <th class="sub-no"><span class="nobr">No.</span></th>
            <th class="sub-amount"><span class="nobr">Initial Amount</span></th>
            <th class="sub-cycle"><span class="nobr">Billing Cycle</span></th>
            <th class="sub-billed"><span class="nobr">Times Billed</span></th>
            <th class="sub-status"><span class="nobr">Status</span></th>
            <th class="sub-item"><span class="nobr">Item</span></th>
            <th class="sub-gateway"><span class="nobr">Gateway</span></th>
            <th class="sub-expiry"><span class="nobr">Expires On</span></th>
            <th class="sub-actions"><span class="nobr">Actions</span></th>
        </tr>
        </thead>

        <tbody>
        <?php
        $i = 1 + $offset;
        foreach ($subs as $sub) {
            ?>
            <tr class="wpinv-sub-items wpinv-sub-item-<?php echo $sub->id; ?> wpinv-sub-item-<?php echo $sub->status; ?>">
                <td><?php echo $i++; ?></td>
                <td><?php echo wpinv_price(wpinv_format_amount($sub->initial_amount), wpinv_get_invoice_currency_code($sub->parent_payment_id)); ?></td>
                <td><?php $frequency = WPInv_Subscriptions::wpinv_get_pretty_subscription_frequency($sub->period, $sub->frequency);
                    $billing = wpinv_price(wpinv_format_amount($sub->recurring_amount), wpinv_get_invoice_currency_code($sub->parent_payment_id)) . ' / ' . $frequency;
                    $initial = wpinv_price(wpinv_format_amount($sub->initial_amount), wpinv_get_invoice_currency_code($sub->parent_payment_id));
                    printf(_x('%s then %s', 'Initial subscription amount then billing cycle and amount', 'invoicing'), $initial, $billing); ?>
                </td>
                <td><?php echo $sub->get_times_billed() . ' / ' . (($sub->bill_times == 0) ? 'Until Cancelled' : $sub->bill_times); ?></td>
                <td><?php echo $sub->get_status_label(); ?></td>
                <td><?php echo get_the_title($sub->product_id); ?></td>
                <td><?php echo wpinv_get_gateway_admin_label(wpinv_get_payment_gateway($sub->parent_payment_id)); ?></td>
                <td><?php echo date_i18n(get_option('date_format'), strtotime($sub->expiration, current_time('timestamp'))); ?></td>
                <td><?php
                    if ($sub->can_cancel()) {
                        echo '<a class="button button-primary" href="' . $sub->get_cancel_url() . '" >' . __("Cancel", "invoicing") . '</a>';
                    } ?>
                </td>
            </tr>
            <?php
        } ?>

        </tbody>

    </table>
    <?php
    if ($totalPage > 1) {
        echo '<div class="sub-pagination">' . paginate_links(array(
                'base' => add_query_arg('cpage', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo; Prev'),
                'next_text' => __('Next &raquo;'),
                'total' => $totalPage,
                'current' => $page
            )) . '</div>';
    }
} else {
    echo '<div class="wpinv-sub-empty alert-info">';
    _e("No Subscriptions found.", "invoicing");
    echo '</div>';
}
do_action('wpinv_after_user_subscriptions', $subs);
?>
