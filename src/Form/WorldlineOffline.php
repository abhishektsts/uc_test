<?php

namespace Drupal\uc_worldline\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_worldline\Plugin\Ubercart\PaymentMethod\Worldline as Worldline;
/**
 * Returns the form for the custom Review Payment screen for Express Checkout.
 */
class WorldlineOffline extends FormBase  {

/**
   * The order that is being reviewed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $currency_code,$merchant_code;
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_worldline_offline_form';
  }

  /**
   * {@inheritdoc}
   */

  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
   
	$form['worldline_merchant_ref_no'] = array(
	      '#type' => 'textfield',
	      '#title' => $this->t('Merchant Ref No'),
	      '#description' => $this->t('Merchant Ref No'),
	      '#default_value' => '',
	      '#size' => 20,
	    );
    $form['Date'] = array(
	      '#type' => 'date',
	      '#title' => $this->t('Date'),
	      '#description' => $this->t('Date'),
	      '#default_value' =>'',
	      '#size' => 20,
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

      if (empty($form_state->getValue('worldline_merchant_ref_no')) ) {
        $form_state->setErrorByName('candidate_number', $this->t("Merchant Ref no. can't be empty."));
      }

    }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state ,OrderInterface $order = NULL) {
  	global $language;
	// $entity_type_manager = \Drupal::service('entity_type.manager');
	
		// drupal_set_message($res);
  }
  public function ajax_save_submit_callback(array &$form, FormStateInterface $form_state){
  	
  	$merchantTxnRefNumber = $form_state->getValue('worldline_merchant_ref_no');
  	
	 $request_array = array(
			"merchant" => array("identifier" => $this->merchant_code),
			"transaction" => array(
				"deviceIdentifier" => "S",
				"currency" => $this->currency_code,
				"identifier" => $merchantTxnRefNumber,
				"dateTime" => $form_state->getValue('Date'),
				"requestType" => "O"
			)
		);
		$refund_data = json_encode($request_array);
		$refund_url = "https://www.paynimo.com/api/paynimoV2.req";
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
		$identifier = $response_array->paymentMethod->paymentTransaction->identifier;
		$amount = $response_array->paymentMethod->paymentTransaction->amount;
		$errorMessage = $response_array->paymentMethod->paymentTransaction->errorMessage;
		$dateTime = $response_array->paymentMethod->paymentTransaction->dateTime;
		$merchantTransactionIdentifier = $response_array->merchantTransactionIdentifier;
		$message = $status_message == true ? $status_message :  "Not Found";
		$res='<div id="edit-output"><div class="container">
			<div class="col-12 col-sm-6">
				<table class="table table-bordered">

					<tbody>
						<tr>
							<th>Status Code</th>
							<th>'.$status_code.'</th>
						</tr>
						<tr>
							<th>Merchant Transaction Reference No</th>
							<th>'.$merchantTransactionIdentifier.'</th>
						</tr>
						<tr>
							<th>TPSL Transaction ID</th>
							<th>'.$identifier.'</th>
						</tr>
						<tr>
							<th>Amount</th>
							<th>'.$amount.'</th>
						</tr>
						<tr>
							<th>Message</th>
							<th>'.$errorMessage.'</th>
						</tr>
						<tr>
							<th>Status Message</th>

							<th>'. $message.'</th>
						</tr>
						<tr>
							<th>Date Time</th>
							<th>'.$dateTime.'</th>
						</tr>

					</tbody>
				</table>
			</div>
		</div></div>';
		// print_r($res);
		// Return the HTML markup we built above in a render array.
  return ['#markup' => $res];

	}


	public function ajax_save_submit(array &$form, FormStateInterface $form_state){

        #Get the names and save 'em in session so they persist if user reloads page
		$response = $form_state->getValue("response");

        $_SESSION['response'] = $response;
	
		$form_state->setValue("response",$response);
		$form_state->setRebuild(TRUE);

	}
}