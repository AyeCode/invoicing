<?php
/**
 * Render the subscriptions table
 *
 * @access      public
 * @since       1.0.0
 * @return      void
 */
function wpinv_recurring_subscriptions_list() {
    ?>
    <div class="wrap">
        <h1>
            <?php _e( 'Subscriptions', 'invoicing' ); ?>
            <a href="<?php echo esc_url( add_query_arg( array( 'wpi_action' => 'add_subscription' ) ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'invoicing' ); ?></a>
        </h1>
        <?php
        //$subscribers_table = new EDD_Subscription_Reports_Table();
        //$subscribers_table->prepare_items();
        ?>

        <form id="subscribers-filter" method="get">
            <input type="hidden" name="post_type" value="download" />
            <input type="hidden" name="page" value="edd-subscriptions" />
            <?php //$subscribers_table->views() ?>
            <?php //$subscribers_table->search_box( __( 'Search', 'invoicing' ), 'subscriptions' ) ?>
            <?php //$subscribers_table->display() ?>
        </form>
    </div>
    <?php
}