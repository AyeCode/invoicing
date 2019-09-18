(function(Cardinal, $) {
    try {
        function set_cruise_result(data, jwt) {
            $('#CardinalOneConnectResult').val(JSON.stringify({
                data: data,
                jwt: jwt
            }));
        }

        function permanent_error(message) {
            var msg = 'CardinalCommerce OneConnect: ' + message;
            console.error(msg);
            set_cruise_result({
                ActionCode: 'ERROR',
                ErrorDescription: message,
                PermanentFatal: true
            });
        }

        if (!$) {
            permanent_error('jQuery could not be loaded');
            return;
        }

        if (!Cardinal) {
            permanent_error('Cruise Songbird library could not be loaded');
            return;
        }

        Cardinal.OneConnect = {};

        Cardinal.configure({
            logging: {
                level: "on"
            },
            CCA: {
                CustomContentID: 'merchant-content-wrapper'
            }
        });

        Cardinal.on('payments.setupComplete', function (setupCompleteData) {
            Cardinal.OneConnect.setupComplete = true;
        });


        Cardinal.OneConnect.clear_results = function () {
            $('#CardinalOneConnectResult').val('');
        };

        Cardinal.on('payments.validated', function (data, jwt) {
            set_cruise_result(data, jwt);
            if (!Cardinal.OneConnect.setupComplete) {
                return;
            }
            alert(data.ActionCode);
            switch(data.ActionCode){
                case "SUCCESS":
                    // Handle successful transaction, send JWT to backend to verify
                    break;

                case "NOACTION":
                    // Handle no actionable outcome
                    break;

                case "FAILURE":
                    // Handle failed transaction attempt
                    break;

                case "ERROR":
                    // Handle service level error
                    break;
            }

            //$form.submit();
        });

        Cardinal.setup('init', { jwt: $('#CardinalOneConnectJWT').val() });

        $('body').bind("wpinv_checkout_submit", function(e, data) {
            var $form = data.form;

            if ($('input[name="wpi-gateway"]:checked', $form).val() != 'paypalpro') {
                return;
            }

            e.preventDefault();

            window.wpiSubmit = false;

            if (!Cardinal.OneConnect.setupComplete) {

                var jwt = $('#CardinalOneConnectJWT').val();
                var month = digits('#ppro-cc-expire-month');
                var month = month.substring(0, 2);
                var year = digits('#ppro-cc-expire-year');
                year = year.substring(2);
                if (year.length == 2) {
                    year = '20' + year;
                }
                var data = {
                    Consumer: {
                        Account: {
                            AccountNumber: digits('#card_number'),
                            ExpirationMonth: month,
                            ExpirationYear: year,
                            CardCode: digits('#ppro-cc-cvv')
                        }
                    }
                };
                Cardinal.start('cca', data, jwt);
                console.log('cardinal start');
            }

        });

        function digits(name) {
            var el = $(name);
            return el.val().replace(/\D/g, '');
        }

    } catch (ex) {
        try {
            permanent_error(ex.toString());
        } catch (e) {}
        throw ex;
    }
})(window.Cardinal, window.jQuery);
