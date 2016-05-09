<?php
//test
use Tygh\Http;

if (!defined('BOOTSTRAP')) 
{
    if (!empty($_REQUEST['OrderID']) && !empty($_REQUEST['HashDigest'])) 
      {
        require './init_payment.php';

        $order_id = (strpos($_REQUEST['OrderID'], '_')) ? substr($_REQUEST['OrderID'], 0, strpos($_REQUEST['OrderID'], '_')) : $_REQUEST['OrderID'];
        //Check hash
        $order_info = fn_get_order_info($order_id);
        $str = 'PreSharedKey=' . $order_info['payment_method']['processor_params']['access_code'] .
               '&MerchantID=' . $order_info['payment_method']['processor_params']['merchant_id'] .
               '&Password=' . $order_info['payment_method']['processor_params']['password'] .
               '&CrossReference=' . $_REQUEST['CrossReference'] .
               '&OrderID=' . $_REQUEST['OrderID'];
        $hash = sha1($str);

        if ($hash == $_REQUEST['HashDigest']) 
        {
            //Check the order status to make shure that it wasn't changed
            $request = array();
            $request['MerchantID'] = $order_info['payment_method']['processor_params']['merchant_id'];
            $request['Password'] = $order_info['payment_method']['processor_params']['password'];
            $request['CrossReference'] = $_REQUEST['CrossReference'];
 
            $_result = Http::post('https://mms.paymentsensegateway.com/Pages/PublicPages/PaymentFormResultHandler.ashx', $request);
 
            parse_str($_result, $result);
            parse_str(urldecode($result['TransactionResult']), $transaction_result);
            $pp_response = array();
            $pp_response['reason_text'] = 'Reason text: ' . $transaction_result['Message'];
            $pp_response['order_status'] = ($transaction_result['StatusCode'] == '0') ? 'P' : 'F';

            if (!empty($_REQUEST['Message'])) 
            {
                $pp_response['reason_text'] .= 'Message: ' . $_REQUEST['Message'];
            }

            $pp_response['transaction_id'] = $_REQUEST['CrossReference'];
            //Place order
            if (fn_check_payment_script('paymentsense_hosted.php', $order_id)) 
            {
                fn_finish_payment($order_id, $pp_response);
            }

            fn_order_placement_routines('route', $order_id, false);
        } 
        else 
        {
            die('Access denied!');
        }
    } 
    else 
    {
        die('Access denied');
    }
}

if (defined('PAYMENT_NOTIFICATION')) 
{
    if ($mode == 'process') 
    {
        //Check the received data
        if (!empty($_REQUEST['OrderID'])) 
        {
            $order_id = intval($_REQUEST['order_id']);
            $status_code = intval($_REQUEST['StatusCode']);
           
            if ($status_code != 'null') 
            {
                $error = '';
                $error_message = '';
                $order_info = fn_get_order_info($order_id);
                switch ($status_code)
                {
            	    case 0:
            	  	  break;
            	    case 4:
            	  	  break;
            	    case 5:
            	  	  break;
            	    case 30:
            	  	  break;
            	    default:
            	  	  break;
                }	
                	
                if ($_REQUEST['MerchantID'] != $order_info['payment_method']['processor_params']['merchant_id']) 
                {
                    $error = 'true';
                    $error_message .= 'Incorrect MerchantID\n';
                }
                if (empty($_REQUEST['Amount']) || $_REQUEST['Amount'] != $order_info['total'] * 100) 
                {
                    $error = 'true';
                    $error_message .= 'Incorrect Price\n';
                }
                if (empty($_REQUEST['CurrencyCode']) || $_REQUEST['CurrencyCode'] != $order_info['payment_method']['processor_params']['currency']) 
                {
                    $error = 'true';
                    $error_message .= 'Incorrect Currency\n';
                }
                if ($_REQUEST['PreviousStatusCode']) 
                {
                    $error = 'true';
                    $error_message .= 'Duplaicated order\n';
                }
                if (!$error) 
                {
                    echo('StatusCode=0');
                }
                else
                {
                    echo('StatusCode=' . $status_code . '&Message=' . $_REQUEST['Message']);
                }
            } 
            else 
            {
                $error = 'true';
                $error_message .= 'Unknown Error Occurred Please Try Payment Again\n';;
            }
        }
    }
}
else 
{
    //define variables	
	$preSharedKey = $processor_data['processor_params']['access_code'];
	$merchantID = $processor_data['processor_params']['merchant_id'];
	$password = $processor_data['processor_params']['password'];
	$amount = $order_info['total'] * 100;
	$currencyCode = $processor_data['processor_params']['currency'];
	$orderID = ($order_info['repaid']) ? ($order_id . '_' . $order_info['repaid']) : $order_id;
	$transactionType = $processor_data['processor_params']['transaction_type'];
	$transactionDateTime = date('Y-m-d H:i:s P');
	$callbackURL = fn_payment_url('current', 'paymentsense_hosted.php'); // it is not allowed to use arguments in this parameter
	$orderDescription = '';
	$customerName = $order_info['b_firstname'] . ' ' . $order_info['b_lastname'];
	$address1 = $order_info['b_address'];
	$address2 = $order_info['b_address_2'];
	$address3 = '';
	$address4 = '';
	$city = $order_info['b_city'];
	$state = $order_info['b_state_descr'];
	$postCode = $order_info['b_zipcode'];
	$countryCode = db_get_field('SELECT code_N3 FROM ?:countries WHERE code=?s', $order_info['b_country']);
	$cv2Mandatory = $processor_data['processor_params']['cv2_mandatory'];
	$address1Mandatory = $processor_data['processor_params']['address_mandatory'];
	$cityMandatory = $processor_data['processor_params']['city_mandatory'];
	$postCodeMandatory = $processor_data['processor_params']['postcode_mandatory'];
	$stateMandatory = $processor_data['processor_params']['state_mandatory'];
	$countryMandatory = $processor_data['processor_params']['country_mandatory'];
	$resultDeliveryMethod = 'SERVER';
	$ServerResultURL = fn_url("payment_notification.process?payment=paymentsense_hosted&order_id=$order_id&fake=true", AREA, 'current');
	$paymentFormDisplaysResult = 'false';
	$serverResultURLCookieVariables = '';
	$serverResultURLFormVariables = '';
	$serverResultURLQueryStringVariables = '';
	
    $post = array('Amount' => $amount,
                  'CurrencyCode' => $currencyCode,
                  'OrderID' => $order_id,
                  'TransactionType' => $transactionType,
                  'TransactionDateTime' => $transactionDateTime,
                  'CallbackURL' => $callbackURL,
                  'OrderDescription' => $orderDescription,
                  'CustomerName' => $customerName,
                  'Address1' => $address1,
                  'Address2' => $address2,
                  'Address3' => $address3,
                  'Address4' => $address4,
                  'City' => $city,
                  'State' => $state,
                  'PostCode' => $postCode,
                  'CountryCode' => $countryCode,
                  'CV2Mandatory' => $cv2Mandatory,
                  'Address1Mandatory' => $address1Mandatory,
                  'CityMandatory' => $cityMandatory,
                  'PostCodeMandatory' => $postCodeMandatory,
                  'StateMandatory' => $stateMandatory,
                  'CountryMandatory' => $countryMandatory,
                  'ResultDeliveryMethod' => $resultDeliveryMethod,
                  'ServerResultURL' => $ServerResultURL,
                  'PaymentFormDisplaysResult' => $paymentFormDisplaysResult,
                  'ServerResultURLCookieVariables' => $serverResultURLCookieVariables,
                  'ServerResultURLFormVariables' => $serverResultURLFormVariables,
                  'ServerResultURLQueryStringVariables' => $serverResultURLQueryStringVariables);
    
    //build Hash Digest
    $str = 'PreSharedKey=' . $preSharedKey .
           '&MerchantID=' . $merchantID .
           '&Password=' . $password.
           '&Amount=' . $amount.
           '&CurrencyCode=' . $currencyCode.
           '&OrderID=' . $order_id.
           '&TransactionType=' . $transactionType.
           '&TransactionDateTime=' . $transactionDateTime.
           '&CallbackURL=' . $callbackURL.
           '&OrderDescription=' . $orderDescription.
           '&CustomerName=' . $customerName.
           '&Address1=' . $address1.
           '&Address2=' . $address2.
           '&Address3=' . $address3.
           '&Address4=' . $address4.
           '&City=' . $city.
           '&State=' . $state.
           '&PostCode=' . $postCode.
           '&CountryCode=' . $countryCode.
           '&CV2Mandatory=' . $cv2Mandatory.
           '&Address1Mandatory=' . $address1Mandatory.
           '&CityMandatory=' . $cityMandatory.
           '&PostCodeMandatory=' . $postCodeMandatory.
           '&StateMandatory=' . $stateMandatory.
           '&CountryMandatory=' . $countryMandatory.
           '&ResultDeliveryMethod=' . $resultDeliveryMethod.
           '&ServerResultURL=' . $ServerResultURL.
           '&PaymentFormDisplaysResult=' . $paymentFormDisplaysResult.
           '&ServerResultURLCookieVariables=' . $serverResultURLCookieVariables.
           '&ServerResultURLFormVariables=' . $serverResultURLFormVariables.
           '&ServerResultURLQueryStringVariables=' . $serverResultURLQueryStringVariables;
    $hashdigest = sha1($str);
    
    //Build form Post
    $post_data['HashDigest'] = $hashdigest;
    $post_data['MerchantID'] = $merchantID;
    $post_data['Amount'] = $amount;
    $post_data['CurrencyCode'] = $currencyCode;
    $post_data['OrderID'] = $order_id;
    $post_data['TransactionType'] = $transactionType;
    $post_data['TransactionDateTime'] = $transactionDateTime;
    $post_data['CallbackURL'] = $callbackURL;
    $post_data['OrderDescription'] = $orderDescription;
    $post_data['CustomerName'] = $customerName;
    $post_data['Address1'] = $address1;
    $post_data['Address2'] = $address2;
    $post_data['Address3'] = $address3;
    $post_data['Address4'] = $address4;
    $post_data['City'] = $city;
    $post_data['State'] = $state;
    $post_data['PostCode'] = $postCode;
    $post_data['CountryCode'] = $countryCode;
    $post_data['CV2Mandatory'] = $cv2Mandatory;
    $post_data['Address1Mandatory'] = $address1Mandatory;
    $post_data['CityMandatory'] = $cityMandatory;
    $post_data['PostCodeMandatory'] = $postCodeMandatory;
    $post_data['StateMandatory'] = $stateMandatory;
    $post_data['CountryMandatory'] = $countryMandatory;
    $post_data['ResultDeliveryMethod'] = $resultDeliveryMethod;
    $post_data['ServerResultURL'] = $ServerResultURL;
    $post_data['PaymentFormDisplaysResult'] = $paymentFormDisplaysResult;
    $post_data['ServerResultURLCookieVariables'] = $serverResultURLCookieVariables;
    $post_data['ServerResultURLFormVariables'] = $serverResultURLFormVariables;
    $post_data['ServerResultURLQueryStringVariables'] = $serverResultURLQueryStringVariables;

    $submit_url = 'https://mms.paymentsensegateway.com/Pages/PublicPages/PaymentForm.aspx';
    fn_create_payment_form($submit_url, $post_data, 'PaymentSense server', False);
}
exit;
