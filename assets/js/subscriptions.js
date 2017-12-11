var WPInv_Admin;
jQuery(document).ready(function($) {
    var WPInv_Recurring = {
        init: function() {
            //Recurring select field conditionals
            this.edit_product_id();
            this.edit_profile_id();
            this.edit_txn_id();
            this.delete();
        },
        /**
         * Edit Subscription Text Input
         */
        edit_subscription_input: function(link, input) {
            //User clicks edit
            if (link.text() === WPInv_Admin.action_edit) {
                //Preserve current value
                link.data('current-value', input.val());
                //Update text to 'cancel'
                link.text(WPInv_Admin.action_cancel);
            } else {
                //User clicked cancel, return previous value
                input.val(link.data('current-value'));
                //Update link text back to 'edit'
                link.text(WPInv_Admin.action_edit);
            }
        },
        edit_profile_id: function() {
            $('.wpinv-edit-sub-profile-id').on('click', function(e) {
                e.preventDefault();
                var link = $(this);
                var profile_input = $('input.wpinv-sub-profile-id');
                WPInv_Recurring.edit_subscription_input(link, profile_input);
                $('.wpinv-sub-profile-id').toggle();
                $('#wpinv-sub-profile-id-update-notice').slideToggle();
            });
        },
        edit_product_id: function() {
            $('.wpinv-sub-product-id').on('change', function(e) {
                e.preventDefault();
                $('#wpinv-sub-product-update-notice').slideDown();
            });
        },
        edit_txn_id: function() {
            $('.wpinv-edit-sub-transaction-id').on('click', function(e) {
                e.preventDefault();
                var link = $(this);
                var txn_input = $('input.wpinv-sub-transaction-id');
                WPInv_Recurring.edit_subscription_input(link, txn_input);
                $('.wpinv-sub-transaction-id').toggle();
            });
        },
        delete: function() {
            $('.wpinv-delete-subscription').on('click', function(e) {
                if (confirm(WPInv_Admin.delete_subscription)) {
                    return true;
                }
                return false;
            });
        }
    };
    WPInv_Recurring.init();
});