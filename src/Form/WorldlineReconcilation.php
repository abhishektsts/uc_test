<?php

namespace Drupal\uc_worldline\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_worldline\Plugin\Ubercart\PaymentMethod\Worldline as Worldline;
/**
 * Returns the form for the custom Review Payment screen for Express Checkout.
 */
class WorldlineReconcilation extends FormBase  {

/**
   * The order that is being reviewed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $currency_code,$merchant_code,$result;
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_worldline_reoncilation_form';
  }

  /**
   * {@inheritdoc}
   */

  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
   
	$form['from_date'] = array(
	      '#type' => 'date',
	      '#title' => $this->t('From date'),
	      '#description' => $this->t('From date'),
	      '#default_value' => '',
	      '#size' => 20,
	    );
    $form['to_date'] = array(
	      '#type' => 'date',
	      '#title' => $this->t('To Date'),
	      '#description' => $this->t('To Date'),
	      '#default_value' =>'',
	      '#size' => 20,
	      '#maxDate' => 0
	      
	    );
     $form['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
			'#ajax'             => [
				'callback'  => '::ajax_save_submit_callback',
				'wrapper' => 'edit-output',
				
			],

    );
     $form['output'] = [
      '#type' => 'textfield',
      '#size' => '60',
      '#disabled' => TRUE,
      '#value' => 'Output Will be displayed here',      
      '#prefix' => '<div id="edit-output">',
      '#suffix' => '</div>',
    ];
    $config = \Drupal::config('uc_worldline.settings');
    $this->merchant_code=$config->get('worldline_merchant_code');
    $store_config = \Drupal::config('uc_store.settings'); 
    $currency=$store_config->get('currency');
     $this->currency_code=$currency["code"];
    
   

     
    return $form;
  }
  public function validateForm(array &$form, FormStateInterface $form_state) {

      if (empty($form_state->getValue('from_date')) ) {
        $form_state->setErrorByName('from_date', $this->t("From Date can't be empty."));
      }
      else  if (empty($form_state->getValue('to_date')) ){
      	 $form_state->setErrorByName('from_date', $this->t("To Date can't be empty."));
      }
      else{
      	$from_date = strtotime($form_state->getValue('from_date'));
  			$to_date = strtotime($form_state->getValue('to_date'));
      	$this->result = db_query("SELECT * FROM uc_orders WHERE order_status ='pending' and created between '".$from_date."' and '".$to_date."' ");
      }
      
    }

     public function submitForm(array &$form, FormStateInterface $form_state ,OrderInterface $order = NULL) {
  	global $language;
	// $entity_type_manager = \Drupal::service('entity_type.manager');
	
		// drupal_set_message($res);
  }
   
  /**
   * {@inheritdoc}
   */
  public function ajax_save_submit_callback(array &$form, FormStateInterface $form_state){
  	
  	$from_date = strtotime($form_state->getValue('from_date'));
		$to_date = strtotime($form_state->getValue('to_date')+1);
  	$result = db_query("SELECT * FROM uc_orders WHERE order_status ='pending' and created >= '$from_date' and created <='$to_date' ")->fetchAll();
		$merchant_code =  $this->merchant_code;
		$successFullOrdersIds = [];
		foreach($result as $row){

			$order_id = $row->order_id;
			$currency = $row->currency;
			$date_input = date("d-m-Y",$row->created);

			$id = date("d-m-Y",$row->created);

			$query = db_query("SELECT merchantid FROM worldlinedetails WHERE orderid =$order_id" )->fetchAll();

			$merchantTxnRefNumber = $query[0]->merchantid;

			$request_array = array(
				"merchant" => array("identifier" => $merchant_code),
				"transaction" => array(
					"deviceIdentifier" => "S",
					"currency" => $currency,
					"identifier" => $merchantTxnRefNumber,
					"dateTime" => $date_input,
					"requestType" => "O"
				)
			);
			$refund_data = json_encode($request_array);
			$url = "https://www.paynimo.com/api/paynimoV2.req";
			$options = array(
				'http' => array(
					'method'  => 'POST',
					'content' => json_encode($request_array),
					'header' =>  "Content-Type: application/json\r\n" .
					"Accept: application/json\r\n"
				)
			);
			$context     = stream_context_create($options);
			$response_array = json_decode(file_get_contents($url, false, $context));

			$status_code = $response_array->paymentMethod->paymentTransaction->statusCode;
			$status_message = $response_array->paymentMethod->paymentTransaction->statusMessage;
			$txn_id = $response_array->paymentMethod->paymentTransaction->identifier;
			if ($status_code == '0300') {
				$success_ids = $order_id;
				 $order = Order::load($order_id);
				 $order->setStatusId('payment_received')->save();
				$msg = array(
					'success' => TRUE,
					'message' => t('Payment of @amount processed successfully, Worldline transaction id @transaction_id.',
						array('@amount' => $row->order_total, '@transaction_id' => $txn_id)),
					'comment' => t('Worldline transaction ID: @transaction_id',
						array('@transaction_id' => $txn_id)),
					'uid' => $row->uid,
      'log_payment' => FALSE // This field doesn't call uc_payment_enter()
    );
				uc_order_comment_save($order_id, $row->uid, $msg['message'], 'admin');
				uc_order_comment_save($order_id, $row->uid, $msg['message'], 'order', 'processing', FALSE);
				array_push($successFullOrdersIds, $success_ids);
				// $woocommerce_object->update_status('processing');
			} else if ($status_code == "0397" || $status_code == "0399" || $status_code == "0396" || $status_code == "0392") {
				$success_ids = $order_id;
				 $order = Order::load($order_id);
				 $order->setStatusId('cancelled')->save();
				$msg = array(
					'success' => TRUE,
					'message' => t('Payment of @amount not received',
						array('@amount' => $row->order_total)),
					'comment' => t('Worldline transaction ID: @transaction_id',
						array('@transaction_id' => $txn_id)),
					'uid' => $row->uid,
      'log_payment' => FALSE // This field doesn't call uc_payment_enter()
    );
				uc_order_comment_save($order_id, $row->uid, $msg['message'], 'admin');
				uc_order_comment_save($order_id, $row->uid, $msg['message'], 'order', 'cancelled', FALSE);
				array_push($successFullOrdersIds, $success_ids);
			} else {
				null;
			}
		}
  	
		

		if ($successFullOrdersIds) {
			$message = "Updated Order Status for Order ID:  " . implode(", ", $successFullOrdersIds);
		} else {
			$message = "Updated Order Status for Order ID: None";
		}
		// print_r($res);
		// Return the HTML markup we built above in a render array.
  return ['#markup' => $message];

	}


	public function ajax_save_submit(array &$form, FormStateInterface $form_state){

        #Get the names and save 'em in session so they persist if user reloads page
		$response = $form_state->getValue("response");

        $_SESSION['response'] = $response;
	
		$form_state->setValue("response",$response);
		$form_state->setRebuild(TRUE);

	}
}