<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPInv_Payment_Gateway_Cardinal_OneConnect {
    public function __construct() {
        $this->id = 'cardinalpm';
        $this->title = 'Credit Card';
        $this->method_title = '3-D Secure Payment Gateway by CardinalCommerce';

        $this->currencies = array(
	        'ADP' => '020',
	        'AED' => '784',
	        'AFA' => '004',
	        'AFN' => '971',
	        'ALL' => '008',
	        'AMD' => '051',
	        'ANG' => '532',
	        'AOA' => '973',
	        'AON' => '024',
	        'ARS' => '032',
	        'ATS' => '040',
	        'AUD' => '036',
	        'AWG' => '533',
	        'AZM' => '031',
	        'AZN' => '944',
	        'BAM' => '977',
	        'BBD' => '052',
	        'BDT' => '050',
	        'BEF' => '056',
	        'BGL' => '100',
	        'BGN' => '975',
	        'BHD' => '048',
	        'BIF' => '108',
	        'BMD' => '060',
	        'BND' => '096',
	        'BOB' => '068',
	        'BOV' => '984',
	        'BRL' => '986',
	        'BSD' => '044',
	        'BTN' => '064',
	        'BWP' => '072',
	        'BYR' => '974',
	        'BZD' => '084',
	        'CAD' => '124',
	        'CDF' => '976',
	        'CHE' => '947',
	        'CHF' => '756',
	        'CHW' => '948',
	        'CLF' => '990',
	        'CLP' => '152',
	        'CNY' => '156',
	        'COP' => '170',
	        'COU' => '970',
	        'CRC' => '188',
	        'CSD' => '891',
	        'CUC' => '931',
	        'CUP' => '192',
	        'CVE' => '132',
	        'CYP' => '196',
	        'CZK' => '203',
	        'DEM' => '276',
	        'DJF' => '262',
	        'DKK' => '208',
	        'DOP' => '214',
	        'DZD' => '012',
	        'EEK' => '233',
	        'EGP' => '818',
	        'ERN' => '232',
	        'ESP' => '724',
	        'ETB' => '230',
	        'EUR' => '978',
	        'FIM' => '246',
	        'FJD' => '242',
	        'FKP' => '238',
	        'FRF' => '250',
	        'GBP' => '826',
	        'GEL' => '981',
	        'GHC' => '288',
	        'GHS' => '936',
	        'GIP' => '292',
	        'GMD' => '270',
	        'GNF' => '324',
	        'GTQ' => '320',
	        'GWP' => '624',
	        'GYD' => '328',
	        'HKD' => '344',
	        'HNL' => '340',
	        'HRK' => '191',
	        'HTG' => '332',
	        'HUF' => '348',
	        'IDR' => '360',
	        'IEP' => '372',
	        'ILS' => '376',
	        'INR' => '356',
	        'IQD' => '368',
	        'IRR' => '364',
	        'ISK' => '352',
	        'ITL' => '380',
	        'JMD' => '388',
	        'JOD' => '400',
	        'JPY' => '392',
	        'KES' => '404',
	        'KGS' => '417',
	        'KHR' => '116',
	        'KMF' => '174',
	        'KPW' => '408',
	        'KRW' => '410',
	        'KWD' => '414',
	        'KYD' => '136',
	        'KZT' => '398',
	        'LAK' => '418',
	        'LBP' => '422',
	        'LKR' => '144',
	        'LRD' => '430',
	        'LSL' => '426',
	        'LTL' => '440',
	        'LUF' => '442',
	        'LVL' => '428',
	        'LYD' => '434',
	        'MAD' => '504',
	        'MDL' => '498',
	        'MGA' => '969',
	        'MGF' => '450',
	        'MKD' => '807',
	        'MMK' => '104',
	        'MNT' => '496',
	        'MOP' => '446',
	        'MRO' => '478',
	        'MTL' => '470',
	        'MUR' => '480',
	        'MVR' => '462',
	        'MWK' => '454',
	        'MXN' => '484',
	        'MXV' => '979',
	        'MYR' => '458',
	        'MZM' => '508',
	        'MZN' => '943',
	        'NAD' => '516',
	        'NGN' => '566',
	        'NIO' => '558',
	        'NLG' => '528',
	        'NOK' => '578',
	        'NPR' => '524',
	        'NZD' => '554',
	        'OMR' => '512',
	        'PAB' => '590',
	        'PEN' => '604',
	        'PGK' => '598',
	        'PHP' => '608',
	        'PKR' => '586',
	        'PLN' => '985',
	        'PTE' => '620',
	        'PYG' => '600',
	        'QAR' => '634',
	        'ROL' => '642',
	        'RON' => '946',
	        'RSD' => '941',
	        'RUB' => '643',
	        'RUR' => '810',
	        'RWF' => '646',
	        'SAR' => '682',
	        'SBD' => '090',
	        'SCR' => '690',
	        'SDD' => '736',
	        'SDG' => '938',
	        'SEK' => '752',
	        'SGD' => '702',
	        'SHP' => '654',
	        'SIT' => '705',
	        'SKK' => '703',
	        'SLL' => '694',
	        'SOS' => '706',
	        'SRD' => '968',
	        'SRG' => '740',
	        'SSP' => '728',
	        'STD' => '678',
	        'SVC' => '222',
	        'SYP' => '760',
	        'SZL' => '748',
	        'THB' => '764',
	        'TJS' => '972',
	        'TMM' => '795',
	        'TMT' => '934',
	        'TND' => '788',
	        'TOP' => '776',
	        'TPE' => '626',
	        'TRL' => '792',
	        'TRY' => '949',
	        'TTD' => '780',
	        'TWD' => '901',
	        'TZS' => '834',
	        'UAH' => '980',
	        'UGX' => '800',
	        'USD' => '840',
	        'USN' => '997',
	        'UYI' => '940',
	        'UYU' => '858',
	        'UZS' => '860',
	        'VEB' => '862',
	        'VEF' => '937',
	        'VND' => '704',
	        'VUV' => '548',
	        'WST' => '882',
	        'XAF' => '950',
	        'XCD' => '951',
	        'XOF' => '952',
	        'XPF' => '953',
	        'XXX' => '999',
	        'YER' => '886',
	        'YUM' => '891',
	        'ZAR' => '710',
	        'ZMK' => '894',
	        'ZMW' => '967',
	        'ZWD' => '716',
	        'ZWL' => '932',
        );

        $this->instances = array(
	        'STAG' => 'centineltest.cardinalcommerce.com',
	        'CYBERSOURCE' => 'cybersource.cardinalcommerce.com',
	        'FIRSTDATA' => 'production.altpayfirstdata.com',
	        'FIRSTDATA_TEST' => 'test.altpayfirstdata.com',
	        'PAYMENTECH' => 'paymentech.cardinalcommerce.com',
	        'PAYPAL' => 'paypal.cardinalcommerce.com',
	        '200' => 'centinel.cardinalcommerce.com',
	        '300' => 'centinel300.cardinalcommerce.com',
	        '400' => 'centinel400.cardinalcommerce.com',
	        'PROD' => 'centinel600.cardinalcommerce.com',
	        '800' => 'centinel800.cardinalcommerce.com',
	        '1000' => 'centinel1000.cardinalcommerce.com',
	        '1200' => 'centinel1200.cardinalcommerce.com',
        );

	    add_filter( 'wp_enqueue_scripts', array($this, 'register_scripts') );
	    add_filter( 'wpinv_purchase_form_before_submit', array($this, 'purchase_form_before_submit') );

    }

    public function register_scripts() {
        $songbird_domain = 'songbird.cardinalcommerce.com';
        /*if ($this->get_option('environment') == 'STAG') {
            $songbird_domain = 'songbirdstag.cardinalcommerce.com';
        }*/
        wp_register_script(
            'cardinalcommerce-oneconnect-songbird',
            "https://{$songbird_domain}/edge/v1/songbird.js");
        wp_register_script(
            'cardinalcommerce-oneconnect', WPINV_PLUGIN_URL.'assets/js/cardinalcommerce-oneconnect.js',
            array('jquery', 'cardinalcommerce-oneconnect-songbird'),
	        WPINV_VERSION, true);
    }

    private static function base64_encode_urlsafe($source) {
        $rv = base64_encode($source);
        $rv = str_replace('=', '', $rv);
        $rv = str_replace('+', '-', $rv);
        $rv = str_replace('/', '_', $rv);
        return $rv;
    }

    private static function base64_decode_urlsafe($source) {
        $s = $source;
        $s = str_replace('-', '+', $s);
        $s = str_replace('_', '/', $s);
        $s = str_pad($s, strlen($s) + strlen($s) % 4, '=');
        $rv = base64_decode($s);
        return $rv;
    }

    public function sign_jwt($header, $body) {
        $secret = '863ef1c5-6a63-48ee-a711-20f1babb570f';
        $plaintext = $header . '.' . $body;
        return self::base64_encode_urlsafe(hash_hmac(
            'sha256', $plaintext, $secret, true));
    }

    private function generate_jwt($data) {
        $header = self::base64_encode_urlsafe(json_encode(array(
            'alg' => 'HS256', 'typ' => 'JWT'
        )));
        $body = self::base64_encode_urlsafe(json_encode($data));
        $signature = $this->sign_jwt($header, $body);
        return $header . '.' . $body . '.' . $signature;
    }

    private function generate_cruise_jwt($invoice = null) {
        $iat = time();
        $data = array(
            'jti' => uniqid(),
            'iat' => $iat,
            'exp' => $iat + 7200,
            'iss' => '5d79e83d031e732958e19532',
            'OrgUnitId' => '5d79e83de0919f19584569b6',
        );
        if ( $invoice ) {
            $payload = $this->create_request_order_object($invoice);
            $data['Payload'] = $payload;
            $data['ObjectifyPayload'] = true;
        }
        $rv = $this->generate_jwt($data);
        return $rv;
    }

    public function parse_cruise_jwt($jwt) {
        $split = explode('.', $jwt);
        if (count($split) != 3) {
            return;
        }
        list($header, $body, $signature) = $split;
        if ($signature != $this->sign_jwt($header, $body)) {
            return;
        }
        $payload = json_decode(self::base64_decode_urlsafe($body));
        return $payload;
    }

    public function hidden_input($id, $value = '') {
        echo "<input type='hidden' id='{$id}' value='{$value}' />";
    }

    public function purchase_form_before_submit() {
        wp_enqueue_script('cardinalcommerce-oneconnect');
	    $invoice = wpinv_get_invoice_cart();
	    $jwt = $this->generate_cruise_jwt($invoice);
	    $this->hidden_input('CardinalOneConnectJWT', $jwt);
	    $this->hidden_input('CardinalOneConnectLoggingLevel','verbose');

        $id = 'CardinalOneConnectResult';
        $merchant_content = 'Consumer Messaging';
        echo "<input type='hidden' autocomplete='off' id='{$id}' name='$id' /><div id='merchant-content-wrapper' style='display: none'><div id='actual-merchant-content'>{$merchant_content}</div></div>";
    }

    public function pm_message($type, $orderid, $amount, $currency, $fields=array()) {
        $timestamp = time() * 1000;
        $plaintext = $timestamp . '9b11d472-91c9-4c5d-aadf-c32e710db171';
        $signature = base64_encode(hash('sha256', $plaintext, true));
        $msg = array(
            'Version' => '1.7',
            'TransactionType' => 'CC',
            'MsgType' => "cmpi_{$type}",
            'OrgUnit' => '5d763f6fe0919f19583ea3e7',
            'OrderId' => $orderid,
            'Amount' => $amount,
            'CurrencyCode' => $this->currency_numeric($currency),
            'Identifier' => '5d763f6f031e732958da85c9',
            'Algorithm' => 'SHA-256',
            'Timestamp' => $timestamp,
            'Signature' => $signature,
        );
        $msg = array_merge($msg, $fields);
        return $msg;
    }

    public function mpi_xml($msg) {
        $rv = '<CardinalMPI>';
        foreach ($msg as $k => $v) {
            $v = str_replace('&', '&amp;', $v);
            $v = str_replace('<', '&lt;', $v);
            $rv .= "<{$k}>{$v}</{$k}>";
        }
        $rv .= '</CardinalMPI>';
        return $rv;
    }

    public function parse_mpi_xml($xml) {
        if (strpos($xml, '<CardinalMPI>') === false) {
            return "No mpi response received from centinel";
        }
        $msg = array();
        $fields = array(
            'AuthorizationCode', 'AVSResult', 'CardCodeResult', 'ErrorDesc',
            'ErrorNo', 'MerchantData', 'MerchantReferenceNumber', 'OrderId',
            'OrderNumber', 'ProcessorOrderNumber', 'ProcessorStatusCode',
            'ProcessorTransactionId', 'ReasonCode', 'ReasonDesc', 'StatusCode',
            'TransactionId',
        );
        foreach ($fields as $key) {
            $value = '';
            if (preg_match("{<{$key}>([^<]*)</{$key}>}", $xml, $m)) {
                $value = $m[1];
            }
            $msg[$key] = $value;
        }
        return $msg;
    }

    public function pm_send_message($msg) {
        $env = 'STAG';
        //$env = $this->get_option('environment');
        $mpi_domain = $this->instances[$env];
        $maps_url = "https://{$mpi_domain}/maps/txns.asp";
        $xml = $this->mpi_xml($msg);
        $response = wp_remote_post($maps_url, array(
            'method' => 'POST',
            'timeout' => 65,
            'body' => array('cmpi_msg' => $xml),
        ));
        if (is_wp_error($response)) {
            return $response->get_error_message();
        }
        $body = wp_remote_retrieve_body($response);
        if (!$body) {
            return "No response received from centinel";
        }
        return $this->parse_mpi_xml($body);
    }

    public function format_mpi_error($response) {
        $rv = $response['ErrorDesc'];
        if ($response['ErrorNo']) {
            $rv .= " ({$response['ErrorNo']})";
        }
        if ($response['ReasonDesc']) {
            $rv .= " {$response['ReasonDesc']}";
        }
        if ($response['ReasonCode']) {
            $rv .= " ({$response['ReasonCode']})";
        }
        return $rv;
    }

    public function reject_with_error($message, $permanent = false) {
        wpinv_set_error('wpinv_error', "{$this->method_title}: {$message}");
	    wpinv_send_back_to_checkout( '?payment-mode=paypalpro' );
    }

    public function order_add($invoice, $key, $value) {
        update_post_meta($invoice->ID, "_{$this->id}_{$key}", $value);
    }

    public function order_get($invoice, $key) {
        $meta = get_post_meta($invoice->ID, "_{$this->id}_{$key}");
        return isset($meta[0]) ? $meta[0] : null;
    }

    public function status_message($invoice, $message, $amount = null,
                                   $error = null) {
        if (!$amount) {
            $amount = $invoice->get_total();
        }
        $price = wpinv_price($amount, array('currency' => $invoice->get_currency()));
        $rv = "{$this->method_title}: {$message} for $price";
        if (isset($error)) {
            $rv .= " - {$error}";
        }
        return $rv;
    }

    public function process_payment( $invoice_id ) {
	    $invoice = wpinv_get_invoice( $invoice_id );

        $cruise_result_json = $_POST['CardinalOneConnectResult'];
        if ( ! $cruise_result_json ) {
            $jwt = $this->generate_cruise_jwt($invoice);
            wp_send_json(array(
                'messages' =>
                    "<script>Cardinal.OneConnect.start('{$jwt}')</script>"
            ));
            exit;
        }

        $cruise_result = json_decode(stripslashes($cruise_result_json));
        $data = $cruise_result->data;
        $this->order_add($invoice, "ActionCode", $data->ActionCode);

        switch ($data->ActionCode) {
        case 'SUCCESS':
        case 'NOACTION':
            break;
        case 'FAILURE':
            $this->reject_with_error('Payment was unsuccessful. ' .
                'Please try again or provide another form of payment.');
            break;
        case 'ERROR':
            $message = $data->ErrorDescription;
            if ( isset($data->ErrorNumber) ) {
                $message .= " ({$data->ErrorNumber})";
            }
            $this->reject_with_error($message, isset($data->PermanentFatal));
            break;
        default:
            $this->reject_with_error('Unknown ActionCode');
            break;
        }

        if (!isset($cruise_result->jwt)) {
            $this->reject_with_error('Missing jwt');
        }

        $jwt = $this->parse_cruise_jwt($cruise_result->jwt);
        if (!$jwt) {
            $this->reject_with_error('Failed to parse jwt');
        }

        $payload = $jwt->Payload;
        if ($payload->ActionCode != $data->ActionCode) {
            $this->reject_with_error('data and Payload ActionCode do not match');
        }

	    $invoiceid = $payload->AuthorizationProcessor->ProcessorOrderId;
        $cca = $payload->Payment->ExtendedData;
        $eci = isset($cca->ECIFlag) ? $cca->ECIFlag : '';
        $cavv = isset($cca->CAVV) ? $cca->CAVV : '';
        $xid = isset($cca->XID) ? $cca->XID : '';

        $currency = $invoice->get_currency();
        $amount = self::raw_amount($invoice->get_total(), $currency);
        $msg = $this->pm_message(
            'authorize', $invoiceid, $amount, $currency, array(
                'Eci' => $eci,
                'Cavv' => $cavv,
                'Xid' => $xid,
                'OrderNumber' => $invoice->get_order_number(),
                'EMail' => $invoice->get_billing_email(),
                "BillingFirstName" => $invoice->get_billing_first_name(),
                "BillingLastName" => $invoice->get_billing_last_name(),
                "BillingAddress1" => $invoice->get_billing_address_1(),
                "BillingAddress2" => $invoice->get_billing_address_2(),
                "BillingCity" => $invoice->get_billing_city(),
                "BillingState" => $invoice->get_billing_state(),
                "BillingPostalCode" => $invoice->get_billing_postcode(),
                "BillingCountryCode" => $invoice->get_billing_country(),
                "BillingPhone" => $invoice->get_billing_phone(),
                "ShippingFirstName" => $invoice->get_shipping_first_name(),
                "ShippingLastName" => $invoice->get_shipping_last_name(),
                "ShippingAddress1" => $invoice->get_shipping_address_1(),
                "ShippingAddress2" => $invoice->get_shipping_address_2(),
                "ShippingCity" => $invoice->get_shipping_city(),
                "ShippingState" => $invoice->get_shipping_state(),
                "ShippingPostalCode" => $invoice->get_shipping_postcode(),
                "ShippingCountryCode" => $invoice->get_shipping_country(),
            )
        );
        $auth_response = $response = $this->pm_send_message($msg);
        if (!is_array($response)) {
            $this->reject_with_error($response);
        }
        $auth_status = $response['StatusCode'];
        if ($auth_status == 'E' && $response['ReasonCode'] == '4' &&
                preg_match('/^25[23] /', $response['ReasonDesc'])) {
            $auth_status = 'P';
        }
        $this->order_add($invoice, 'AuthorizationStatus', $auth_status);
        if (!in_array($auth_status, array('Y', 'P'))) {
            $this->reject_with_error($this->format_mpi_error($response));
        }

        if ($auth_status == 'Y' &&
                $this->get_option('paymentAuthType') == 'AUTH_CAPTURE') {
            $msg = $this->pm_message('capture', $invoiceid, $amount, $currency);
            $response = $this->pm_send_message($msg);
            $void = $this->pm_message('void', $invoiceid, $amount, $currency);
            if (!is_array($response)) {
                $this->pm_send_message($void);
                $this->reject_with_error($response);
            }
            if ($response['StatusCode'] != 'Y') {
                $this->pm_send_message($void);
                $this->reject_with_error($this->format_mpi_error($response));
            }

            $this->order_add($invoice, 'CaptureStatus', $response['StatusCode']);
            $invoice->add_order_note($this->status_message(
                $invoice, 'Payment authorized and captured'));
            $invoice->payment_complete($invoiceid);
        } else {
            $invoice->set_transaction_id($invoiceid);
            if ($auth_status == 'Y') {
                $invoice->update_status('on-hold',
                    $this->status_message($invoice, 'Payment authorized'));
            } else {
                $invoice->update_status('on-hold',
                    $this->status_message($invoice, 'Payment held for review. Please, login to your processor account to manage this order.'));
            }
            $invoice->reduce_order_stock();
            WC()->cart->empty_cart();
        }

        foreach ($auth_response as $key => $value) {
            $this->order_add($invoice, $key, $value);
        }

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url( $invoice )
        );
    }

    public function currency_numeric($alpha) {
        return isset($this->currencies[$alpha]) ?
            $this->currencies[$alpha] : null;
    }

    public static function currency_exponent($alpha) {
        if (in_array($alpha, array(
            'ADP', 'BEF', 'BIF', 'BYR', 'CLP', 'DJF', 'ESP', 'GNF', 'ISK',
            'ITL', 'JPY', 'KMF', 'KRW', 'LUF', 'MGF', 'PTE', 'PYG', 'RWF',
            'TPE', 'TRL', 'UYI', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
        ))) {
            return 0;
        } elseif (in_array($alpha, array(
            'BHD', 'CSD', 'IQD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND',
        ))) {
            return 3;
        } elseif ($alpha == 'CLF') {
            return 4;
        }
        return 2;
    }

    public static function raw_amount($amount, $currency_alpha) {
        $float_amount = (float) $amount;
        $exponent = self::currency_exponent($currency_alpha);
        $int_amount = (int) round($float_amount * pow(10, $exponent));
        return (string) $int_amount;
    }

    public function create_request_order_object($invoice) {
        $currency = $invoice->get_currency();
    	$currency_alpha = $this->currencies[$currency];
        $raw_amount = self::raw_amount($invoice->get_total(), $currency_alpha);

        $request_order_object = array(
            "Consumer" => array(
                "BillingAddress" => array(
                    "FirstName" => $invoice->get_first_name(),
                    "LastName" => $invoice->get_last_name(),
                    "Address1" => $invoice->get_address(),
                    "City" => $invoice->city,
                    "State" => $invoice->state,
                    "PostalCode" => $invoice->zip,
                    "CountryCode" => $invoice->country,
                    "Phone1" => $invoice->phone,
                ),
                "Email1" => $invoice->email,
            ),
            "OrderDetails" => array(
                "OrderNumber" => $invoice->ID,
                "Amount" => $raw_amount,
                "CurrencyCode" => $currency_alpha,
                "OrderChannel" => "S",
            ),
            "Options" => array(
                "EnableCCA" => 'yes',
            ),
        );

        return $request_order_object;
    }

}

new WPInv_Payment_Gateway_Cardinal_OneConnect();