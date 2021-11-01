<?php

namespace Drupal\uc_worldline\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_cart\CartManagerInterface;
use Drupal\uc_order\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for uc_worldline.
 */
class WorldlineController extends ControllerBase
{


    public function worldlineRequest()
    {

        $post_params = \Drupal::request()->request->all();
        $payment_order_mode =  explode(",", $post_params["payment_order_mode_raw"]);
        $payment_order_mode[0] = (isset($payment_order_mode[0])) ? $payment_order_mode[0] : null;
    $payment_order_mode[1] = (isset($payment_order_mode[1])) ? $payment_order_mode[1] : null;
    $payment_order_mode[2] = (isset($payment_order_mode[2])) ? $payment_order_mode[2] : null;
    $payment_order_mode[3] = (isset($payment_order_mode[3])) ? $payment_order_mode[3] : null;
    $payment_order_mode[4] = (isset($payment_order_mode[4])) ? $payment_order_mode[4] : null;
    $payment_order_mode[5] = (isset($payment_order_mode[5])) ? $payment_order_mode[5] : null;
    $payment_order_mode[6] = (isset($payment_order_mode[6])) ? $payment_order_mode[6] : null;
    $payment_order_mode[7] = (isset($payment_order_mode[7])) ? $payment_order_mode[7] : null;
    $payment_order_mode[8] = (isset($payment_order_mode[8])) ? $payment_order_mode[8] : null;
    $payment_order_mode[9] = (isset($payment_order_mode[9])) ? $payment_order_mode[9] : null;
    if (!$payment_order_mode) {
      $paymentModeOrder = ["wallets", "cards", "netBanking", "imps", "cashCards", "UPI", "MVISA", "debitPin", "emiBanks", "NEFTRTGS"];
    } else {
      $paymentModeOrder = [
        $payment_order_mode[0],
        $payment_order_mode[1],
        $payment_order_mode[2],
        $payment_order_mode[3],
        $payment_order_mode[4],
        $payment_order_mode[5],
        $payment_order_mode[6],
        $payment_order_mode[7],
        $payment_order_mode[8],
        $payment_order_mode[9]

      ];
    }
    $newPaymentOrder="[";
    for($i=0;$i<count($paymentModeOrder);$i++){
        $newPaymentOrder.='"'.$paymentModeOrder[$i].'",';
    }
    $newPaymentOrder=rtrim($str,",");
    $newPaymentOrder.="]";
    
        
        $checkoutConfig = "<div id='worldline_payment_form'>
            </div>
        <div align='center'>Please wait while the request is being tranferred to the payment gateway. Do not refresh your browser at this moment</div>
        <form action='" . $post_params['returnUrl'] . "' id='response-form' method='POST'>
                <input type='hidden' name='responsemsg' value='' id='responsemsg'>
        </form>
        <script src='https://www.paynimo.com/paynimocheckout/client/lib/jquery.min.js' type='text/javascript'></script>
        <script type='text/javascript' src='https://www.paynimo.com/paynimocheckout/server/lib/checkout.js'></script>
        <script type='text/javascript'>
        $(document).ready(function() {
            function handleResponse(res) {
                if (typeof res != 'undefined' && typeof res.paymentMethod != 'undefined' && typeof res.paymentMethod.paymentTransaction != 'undefined' && typeof res.paymentMethod.paymentTransaction.statusCode != 'undefined' && res.paymentMethod.paymentTransaction.statusCode == '0300') {
                    // success block
                    var stringResponse = res.stringResponse;
                    $('#responsemsg').val(stringResponse);
                            $('#response-form').submit();
                } else if (typeof res != 'undefined' && typeof res.paymentMethod != 'undefined' && typeof res.paymentMethod.paymentTransaction != 'undefined' && typeof res.paymentMethod.paymentTransaction.statusCode != 'undefined' && res.paymentMethod.paymentTransaction.statusCode == '0398') {
                    // initiated block
                } else {
                    // error block
                }
            };

                var configJson = {
                    'tarCall': false,
                    'features': {
                        'showPGResponseMsg': true,
                        'enableAbortResponse': true,
                        'enableExpressPay': true,
                        'enableNewWindowFlow': '" . $post_params['enableNewWindowFlow'] . "'   //for hybrid applications please disable this by passing 
                    },
                    'consumerData': {
                        'deviceId': 'WEBSH2', //possible values 'WEBSH1' or 'WEBSH2'
                        'token': '" . $post_params['token'] . "',
                        'returnUrl': '" . $post_params['returnUrl'] . "',    //merchant response page URL
                        'responseHandler': handleResponse,
                        'checkoutElement':'" . $post_params['checkoutElement'] . "',
                        'paymentModeOrder': " . $newPaymentOrder . ",
                        'merchantMsg':'" . $post_params['merchantMsg'] . "',
                        'txnType':'" . $post_params['txnType'] . "',
                        'saveInstrument':'" . $post_params['saveInstrument'] . "',
                        'paymentMode': '" . $post_params['worldline_payment_mode'] . "',
                        'merchantLogoUrl': '" . $post_params['merchant_logo_url'] . "',  //provided merchant logo will be displayed
                        'merchantId': '" . $post_params['mrctCode'] . "',
                        'currency': '" . $post_params['currency'] . "',
                        'consumerId': '" . $post_params['CustomerId'] . "',
                        'consumerMobileNo': '" . $post_params['customerMobNumber'] . "',
                        'consumerEmailId': '" . $post_params['email'] . "',
                        'txnId': '" . $post_params['merchantTxnRefNumber'] . "',   //Unique merchant transaction ID
                        'items': [{
                            'itemId': '" . $post_params['scheme'] . "',
                            'amount': '" . $post_params['Amount'] . "',
                            'comAmt': '0'
                        }],
                        'cartDescription': '}{custname:' + '" . $post_params['CustomerName'] . "' + '}{orderid:' + '" . $post_params['orderId'] . "',
                            'merRefDetails': [{
                                'name': 'Txn. Ref. ID',
                                'value': '" . $post_params['merchantTxnRefNumber'] . "'
                            }],
                        'customStyle': {
                            'PRIMARY_COLOR_CODE': '" . $post_params['PRIMARY_COLOR_CODE'] . "',   //merchant primary color code
                            'SECONDARY_COLOR_CODE': '" . $post_params['SECONDARY_COLOR_CODE'] . "',   //provide merchant's suitable color code
                            'BUTTON_COLOR_CODE_1': '" . $post_params['BUTTON_COLOR_CODE_1'] . "',   //merchant's button background color code
                            'BUTTON_COLOR_CODE_2': '" . $post_params['BUTTON_COLOR_CODE_2'] . "'   //provide merchant's suitable color code for button text
                        }
                    }
                };

                $.pnCheckout(configJson);
                if(configJson.features.enableNewWindowFlow){
                    pnCheckoutShared.openNewWindow();
                }
        });
    </script>
        ";
        $response = new Response();
        $response->setContent($checkoutConfig);
        return $response;
    }
    public function worldlineResponse()
    {
        $config = \Drupal::config('uc_worldline.settings');
        $identifier = $config->get('worldline_merchant_code');
        $currency = $config->get('currency');
        $worldline_SALT = $config->get('worldline_SALT');
        if ($_POST) {
            $response = $_POST;
            if (is_array($response)) {
                $str = $response['msg'];
            }
            $msg_array = explode('|', $str);
            $status = $msg_array[0];
            if ($status != '') {

                $merchantTxnRefNumber = $msg_array[3];
                $response_message = $msg_array[1];
                $response_message2 = $msg_array[2];
                if ($response_message2 != "NA") {
                    $response_message2 = "Transaction Failed";
                }

                $transaction_id = $msg_array[5];
                //fetch statuscode form response
                $error_status_msg = $this->getErrorStatusMessage($status);
                //fetch orderid from response
                $status2 = $msg_array[7];
                $response_cart = explode('orderid:', $status2);
                $oid_1 = $response_cart[1];
                $oid_2 = explode('}', $oid_1);
                $order_id = $oid_2[0];
                $transauthorised = false;
                $result = db_query("UPDATE  worldlinedetails SET merchantid=$merchantTxnRefNumber WHERE orderid =$order_id");
                $hashstring = array_pop($msg_array);
                $array_without_hash = $msg_array;
                $string_without_hash = implode("|", $array_without_hash);
                $salt_token = $string_without_hash . '|' .  $worldline_SALT;
                $hashed_string_token = hash('sha512', $salt_token);
                if ($order_id != '') {
                    $order = Order::load($order_id);
                    if (!$order || $order->getStateId() != 'Completed') {
                        if ($status == '300') {
                            if ($this->s2s_call_maker($identifier,  $currency, $transaction_id) == '0300') {
                                $order->setStatusId('payment_received')->save();
                                uc_order_comment_save($order_id, 0, $this->t('A payment has been accepted through worldline @merchant transaction id  - .' . $merchantTxnRefNumber), 'admin');
                                $session = \Drupal::service('session');
                                $session->set('uc_checkout_complete_' . $order->id(), TRUE);
                                return $this->redirect('uc_cart.checkout_complete');
                            }
                        } else {
                            if ($hashed_string_token != $hashstring) {
                                $msg = 'Transaction Error Message from Payment Gateway: Hash Validation Failed';
                            } else {
                                $msg = 'Transaction Status: ' . $error_status_msg;
                            }
                            $this->s2s_call_maker($identifier, $currency, $transaction_id);
                            drupal_set_message($this->t($msg, []), 'error');
                            return $this->redirect('uc_cart.checkout_review');
                        }
                    }
                }
            } else {
                $msg =  'Error Message: Empty Response from Payment Gateway';
                drupal_set_message($this->t($msg, []), 'error');
                return $this->redirect('uc_cart.checkout_review');
            }
        }
    }

    public function s2s_call_maker($identifier, $currency, $transaction_id)
    {
        $request_array = array(
            "merchant" => array("identifier" => $identifier),
            "transaction" => array(
                "deviceIdentifier" => "S",
                "currency" => $currency,
                "dateTime" => date("Y-m-d"),
                "token" => $transaction_id,
                "requestType" => "S"
            )
        );
        $Scall_url = "https://www.paynimo.com/api/paynimoV2.req";
        $options = array(
            'http' => array(
                'method'  => 'POST',
                'content' => json_encode($request_array),
                'header' =>  "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n"
            )
        );
        $context  = stream_context_create($options);
        $responseString = file_get_contents($Scall_url, false, $context);
        $response_array = json_decode($responseString);
        $status_code = $response_array->paymentMethod->paymentTransaction->statusCode;

        if ($status_code) {
            return $status_code;
        } else {
            return 'Failed';
        }
    }

    public function create_request_logs($str)
    {
        $worldlineModulePath = drupal_get_path('module', 'uc_worldline') . '/logs/';
        $file_name = 'worldline_logs' . date("Y-m-d") . '.log';
        if (!file_exists($file_name)) {
            $myfile = fopen($worldlineModulePath . $file_name, "a");
            $txt =  "\r\n" . "worldline Request:" . $str;
            fwrite($myfile, $txt);
            fclose($myfile);
        }
    }

    function s2s_request()
    {
        $config = \Drupal::config('uc_worldline.settings');
        $worldline_SALT = $config->get('worldline_SALT');
        $s2sresponse = new Response();
        $response = $_GET;
        if (!$response) {
            $resArray1 = json_encode(['massage' => 'No msg parameter in params']);
            $con = $s2sresponse->setContent($resArray1);
            return $con;
            exit;
        }
        if (!$response['msg']) {
            $resArray = json_encode(['massage' => 'Empty parameter']);
            $con = $s2sresponse->setContent($resArray);
            return $con;
            exit;
        }
        if (is_array($response)) {
            $str = $response['msg'];
        }
        $response1 = explode('|', $str);
        $response_message = $response1[1];
        $response_message2 = $response1[2];
        $merchantTxnRefNumber = $response1[3];
        $transaction_id = $response1[5];
        $status = $response1[0];

        $status2 = $response1[7];
        $response_cart = explode('orderid:', $status2);
        $oid_1 = $response_cart[1];
        $oid_2 = explode('}', $oid_1);
        $order_id = $oid_2[0];

        $hashstring = array_pop($response1);
        $array_without_hash = $response1;
        $string_without_hash = implode("|", $array_without_hash);
        $salt_token = $string_without_hash . '|' . $worldline_SALT;
        $hashed_string_token = hash('sha512', $salt_token);
        $worldlineModulePath = drupal_get_path('module', 'uc_worldline') . '/logs/';
        if ($hashstring !== $hashed_string_token) {
            if ($status == '0300') {
                $file_name = 'worldline_logs' . date("Y-m-d") . '.log';
                if (!file_exists($file_name)) {
                    $myfile = fopen($worldlineModulePath . $file_name, "a");
                    $txt =  "\r\n" . "Responce_s2s:" . $str;
                    fwrite($myfile, $txt);
                    fclose($myfile);
                }
                $return_string = $merchantTxnRefNumber . "|" . $transaction_id . "|1";
                $resArray3 = json_encode(['message' => $return_string]);
                $con = $s2sresponse->setContent($resArray3);
                return $con;
            } else {
                $file_name = 'worldline_logs' . date("Y-m-d") . '.log';
                if (!file_exists($file_name)) {
                    $myfile = fopen($worldlineModulePath . $file_name, "a");
                    $txt =  "\r\n" . "Responce_s2s:" . $str;
                    fwrite($myfile, $txt);
                    fclose($myfile);
                }
                $return_string = $merchantTxnRefNumber . "|" . $transaction_id . "|0";
                $resArray4 = json_encode(['message' => $return_string]);
                $con = $s2sresponse->setContent($resArray4);
                return $con;
            }
        } else {
            $resArray5 = json_encode(['message' => 'Hash Fail']);
            $con = $s2sresponse->setContent($resArray5);
            return $con;
        }
    }

    public function getErrorStatusMessage($code)
    {
        $messages = [
            "0300" => "Successful Transaction",
            "0392" => "Transaction cancelled by user either in Bank Page or in PG Card /PG Bank selection",
            "0396" => "Transaction response not received from Bank, Status Check on same Day",
            "0397" => "Transaction Response not received from Bank. Status Check on next Day",
            "0399" => "Failed response received from bank",
            "0400" => "Refund Initiated Successfully",
            "0401" => "Refund in Progress (Currently not in used)",
            "0402" => "Instant Refund Initiated Successfully(Currently not in used)",
            "0499" => "Refund initiation failed",
            "9999" => "Transaction not found :Transaction not found in PG"
        ];
        if (in_array($code, array_keys($messages))) {
            return $messages[$code];
        }
        return null;
    }
}
