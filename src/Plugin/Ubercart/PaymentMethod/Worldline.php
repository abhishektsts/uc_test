<?php

namespace Drupal\uc_worldline\Plugin\Ubercart\PaymentMethod;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_order\Entity\Order;


/**
 * Defines the worldline payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "worldline",
 *   name = @Translation("worldline"),
 *   redirect = "\Drupal\uc_worldline\Form\WorldlineForm",
 * )
 */
class Worldline extends PaymentMethodPluginBase  implements OffsitePaymentMethodPluginInterface
{

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label)
  {
    $build['#attached']['library'][] = 'uc_worldline/worldline.styles';
    $build['label'] = array(
      '#plain_text' => $label,
      '#suffix' => '<br />',
    );
    $build['image'] = array(
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'uc_worldline') . '/images/worldline-mint.png',
      '#alt' => $this->t('worldline'),
      '#attributes' => array('class' => array('uc-worldline-logo')),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return [
      'worldline_merchant_code' => '',
      'worldline_SALT' => '',
      'webservice_locator' => '',
      'worldline_merchant_scheme_code' => '',
      'worldline_payment_mode' => 'all',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $form['worldline_merchant_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Code'),
      '#description' => $this->t('Merchant Code'),
      '#default_value' => $this->configuration['worldline_merchant_code'],
      '#size' => 16,
    );
    $form['worldline_SALT'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('SALT'),
      '#description' => $this->t('SALT'),
      '#default_value' => $this->configuration['worldline_SALT'],
      '#size' => 16,
    );
    $form['webservice_locator'] = array(
      '#type' => 'select',
      '#title' => $this->t('Payment Type'),
      '#description' => $this->t('For TEST mode amount will be charge 1'),
      '#options' => array(
        'Test' => $this->t('Test'),
        'Live' => $this->t('Live'),
      ),
      '#default_value' => $this->configuration['webservice_locator'],
    );
    $form['worldline_merchant_scheme_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Scheme Code'),
      '#description' => $this->t('Merchant Scheme Code'),
      '#default_value' => $this->configuration['worldline_merchant_scheme_code'],
    );
    $form['worldline_success_msg'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Success Message'),
      '#description' => $this->t('Success Message'),
      '#default_value' => $this->t('Thank you for shopping with us. Your account has been charged and your transaction is successful.'),
    );
    $form['worldline_decline_msg'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Decline Message'),
      '#description' => $this->t('Decline Message'),
      '#default_value' => $this->t('Thank you for shopping with us. However, the transaction has been declined.'),
    );
    $form['merchant_logo_url'] = array(
      '#type' => 'url',
      '#title' => $this->t('Merchant Logo URL'),
      '#description' => $this->t('An absolute URL pointing to a logo image of merchant which will show on checkout popup'),
      '#default_value' => $this->t('https://www.paynimo.com/CompanyDocs/company-logo-md.png'),

    );
    $form['PRIMARY_COLOR_CODE'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Primary Color Code'),
      '#description' => $this->t('Color value can be hex, rgb or actual color name'),
      '#default_value' => $this->t('#3977b7'),
    );
    $form['SECONDARY_COLOR_CODE'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('SECONDARY Color Code'),
      '#description' => $this->t('Color value can be hex, rgb or actual color name'),
      '#default_value' => $this->t('#FFFFFF'),
    );
    $form['BUTTON_COLOR_CODE_1'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Button Color Code 1'),
      '#description' => $this->t('Color value can be hex, rgb or actual color name'),
      '#default_value' => $this->t('#1969bb'),
    );
    $form['BUTTON_COLOR_CODE_2'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Button Color Code 2'),
      '#description' => $this->t('Color value can be hex, rgb or actual color name'),
      '#default_value' => $this->t('#FFFFFF'),
    );
    $form['worldline_payment_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Payment Mode'),
      '#description' => $this->t('If Bank selection is at worldline ePayments India Pvt. Ltd. (a Worldline brand) end then select all, if bank selection at Merchant end then pass appropriate mode respective to selected option'),
      '#options' => array(
        'all' => $this->t('all'),
        'cards' => $this->t('cards'),
        'netBanking' => $this->t('netBanking'),
        'UPI' => $this->t('UPI'),
        'imps' => $this->t('imps'),
        'wallets' => $this->t('wallets'),
        'cashCards' => $this->t('cashCards'),
        'NEFTRTGS' => $this->t('NEFTRTGS'),
        'emiBanks' => $this->t('emiBanks'),
      ),
      '#default_value' => $this->configuration['worldline_payment_mode'],
    );
    $form['enableNewWindowFlow'] = array(
      '#type' => 'select',
      '#title' => $this->t('Enable new window flow'),
      '#description' => $this->t('If this feature is enabled, then bank page will open in new window'),
      '#options' => array(
        '0' => $this->t('Disable'),
        '1' => $this->t('Enable'),
      ),
      '#default_value' => 1,
    );
    $form['enableExpressPay'] = array(
      '#type' => 'select',
      '#title' => $this->t('Enable Express Pay'),
      '#description' => $this->t('To enable saved payments set its value to Enable'),
      '#options' => array(
        '0' => $this->t('Disable'),
        '1' => $this->t('Enable'),
      ),
      '#default_value' => 1,
    );
    $form['separateCardMode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Separate Card Mode'),
      '#description' => $this->t('If this feature is enabled checkout shows two separate payment mode(Credit Card and Debit Card'),
      '#options' => array(
        '0' => $this->t('Disable'),
        '1' => $this->t('Enable'),
      ),
      '#default_value' => 1,
    );
    $form['merchantMsg'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Message'),
      '#description' => $this->t('Customize message from merchant which will be shown to customer in checkout page'),
    );
    $form['disclaimerMsg'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Disclaimer Message'),
      '#description' => $this->t('Customize disclaimer message from merchant which will be shown to customer in checkout page'),
    );
    $form['enableMerTxnDetails'] = array(
      '#type' => 'select',
      '#title' => $this->t('Merchant Transaction Details'),
      '#description' => $this->t('Merchant Transaction Details'),
      '#options' => array(
        '0' => $this->t('Disable'),
        '1' => $this->t('Enable'),
      ),
      '#default_value' => 1,
    );
    $form['enableInstrumentDeRegistration'] = array(
      '#type' => 'select',
      '#title' => $this->t('Enable InstrumentDeRegistration'),
      '#description' => $this->t('If this feature is enabled, you will have an option to delete saved cards'),
      '#options' => array(
        '0' => $this->t('Disable'),
        '1' => $this->t('Enable'),
      ),
      '#default_value' => 0,
    );
    $form['hideSavedInstruments'] = array(
      '#type' => 'select',
      '#title' => $this->t('Hide Saved Instruments'),
      '#description' => $this->t('If enabled checkout hides saved payment options even in case of enableExpressPay is enabled.'),
      '#options' => array(
        '0' => $this->t('Disable'),
        '1' => $this->t('Enable'),
      ),
      '#default_value' => 0,
    );
    $form['saveInstrument'] = array(
      '#type' => 'select',
      '#title' => $this->t('Save Instrument'),
      '#description' => $this->t('Enable this feature to vault instrument'),
      '#options' => array(
        '0' => $this->t('Disable'),
        '1' => $this->t('Enable'),
      ),
      '#default_value' => 0,
    );
    $form['txnType'] = array(
      '#type' => 'select',
      '#title' => $this->t('Transaction Type'),
      '#description' => $this->t('Transaction Type'),
      '#options' => array(
        'SALE' => $this->t('SALE'),
      ),
      '#default_value' => 'SALE',
    );
    $form['payment_mode_order'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Payment Mode Order'),
      '#description' => $this->t('Place order in this format: \r\n\r\n cards,netBanking,imps,wallets,cashCards,UPI,MVISA,debitPin,NEFTRTGS,emiBanks'),
      '#default_value' => $this->configuration['payment_mode_order'],
    );
    $form['handle_response_on_popup'] = array(
      '#type' => 'select',
      '#title' => $this->t('Display Transaction Message on Popup'),
      '#description' => $this->t('Handle Response on popup'),
      '#options' => array(
        'no' => $this->t('Disable'),
        'yes' => $this->t('Enable'),
      ),
      '#default_value' => 'no',
    );
    $form['checkoutElement'] = array(
      '#type' => 'select',
      '#title' => $this->t('Embed Payment Gateway On Page'),
      '#description' => $this->t('Embed Payment Gateway On Page'),
      '#options' => array(
        '' => $this->t('Disable'),
        '#worldline_payment_form' => $this->t('Enable'),
      ),
      '#default_value' => $this->configuration['checkoutElement'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    $this->configuration['worldline_merchant_code'] = $form_state->getValue('worldline_merchant_code');
    $this->configuration['worldline_SALT'] = $form_state->getValue('worldline_SALT');
    $this->configuration['webservice_locator'] = $form_state->getValue('webservice_locator');
    $this->configuration['worldline_merchant_scheme_code'] = $form_state->getValue('worldline_merchant_scheme_code');
    $this->configuration['worldline_success_msg'] = $form_state->getValue('worldline_success_msg');
    $this->configuration['worldline_decline_msg'] = $form_state->getValue('worldline_decline_msg');
    $this->configuration['merchant_logo_url'] = $form_state->getValue('merchant_logo_url');
    $this->configuration['PRIMARY_COLOR_CODE'] = $form_state->getValue('PRIMARY_COLOR_CODE');
    $this->configuration['SECONDARY_COLOR_CODE'] = $form_state->getValue('SECONDARY_COLOR_CODE');
    $this->configuration['PRIMARY_COLOR_CODE'] = $form_state->getValue('PRIMARY_COLOR_CODE');
    $this->configuration['BUTTON_COLOR_CODE_1'] = $form_state->getValue('BUTTON_COLOR_CODE_1');
    $this->configuration['BUTTON_COLOR_CODE_2'] = $form_state->getValue('BUTTON_COLOR_CODE_2');
    $this->configuration['worldline_payment_mode'] = $form_state->getValue('worldline_payment_mode');
    $this->configuration['enableNewWindowFlow'] = $form_state->getValue('enableNewWindowFlow');
    $this->configuration['enableExpressPay'] = $form_state->getValue('enableExpressPay');
    $this->configuration['separateCardMode'] = $form_state->getValue('separateCardMode');
    $this->configuration['merchantMsg'] = $form_state->getValue('merchantMsg');
    $this->configuration['disclaimerMsg'] = $form_state->getValue('disclaimerMsg');
    $this->configuration['enableMerTxnDetails'] = $form_state->getValue('enableMerTxnDetails');
    $this->configuration['enableInstrumentDeRegistration'] = $form_state->getValue('enableInstrumentDeRegistration');
    $this->configuration['hideSavedInstruments'] = $form_state->getValue('hideSavedInstruments');
    $this->configuration['saveInstrument'] = $form_state->getValue('saveInstrument');
    $this->configuration['txnType'] = $form_state->getValue('txnType');
    $this->configuration['payment_mode_order'] = $form_state->getValue('payment_mode_order');
    $this->configuration['handle_response_on_popup'] = $form_state->getValue('handle_response_on_popup');
    $this->configuration['checkoutElement'] = $form_state->getValue('checkoutElement');
  }

  /**
   * {@inheritdoc}
   */


  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state)
  {
    $session = \Drupal::service('session');
    if (NULL != $form_state->getValue(['panes', 'payment', 'details', 'pay_method'])) {
      $session->set('pay_method', $form_state->getValue(['panes', 'payment', 'details', 'pay_method']));
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReviewTitle()
  {
    if ($this->configuration['worldline_payment_mode']) {
      return $this->t('worldline');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL)
  {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('uc_worldline.settings');
    $config->set('worldline_merchant_code', $this->configuration['worldline_merchant_code']);
    $config->set('currency', $order->getCurrency());
    $config->set('worldline_SALT', $this->configuration['worldline_SALT']);
    $config->set('checkoutElement', $this->configuration['checkoutElement']);
    $config->set('payment_mode_order', $this->configuration['payment_mode_order']);
    $config->save(TRUE);
    $address = $order->getAddress('billing');
    $form['#attached']['library'][] = 'uc_worldline/offsite_redirect';

    if ($this->configuration['webservice_locator'] == 'Test') {
      $amount = '1.00';
    } else {
      $amount = uc_currency_format($order->getTotal(), FALSE, FALSE, '.');
    }
    if ($address->country) {
      $country = \Drupal::service('country_manager')->getCountry($address->country)->getAlpha3();
    } else {
      $country = '';
    }
    $customerMobNumber = Unicode::substr($address->phone, 0, 16);
    if (strpos($customerMobNumber, '+') !== false) {
      $customerMobNumber = str_replace("+", "", $customerMobNumber);
    }
    $merchantTxnRefNumber = rand(1, 1000000);
    $order_id = $order->id();
    $result = db_query("INSERT into worldlinedetails(orderid,merchantid) values('$order_id','$merchantTxnRefNumber')");
    if (!$order->getOwnerId()) {
      $cusid_raw = rand(1, 1000000);
    } else {
      $cusid_raw = $order->getOwnerId();
    }

    if ($this->configuration['handle_response_on_popup'] == 'yes' &&  (int)$this->configuration['enableNewWindowFlow'] == 1) {
      $returnUrl = '';
    } else if ($this->configuration['handle_response_on_popup'] == 'no' && (int)$this->configuration['enableNewWindowFlow'] == 1) {
      $returnUrl = Url::fromRoute('uc_worldline.payment_response')->toString();
    } else {
      $returnUrl = Url::fromRoute('uc_worldline.payment_response')->toString();
    }
    if ($this->configuration['enableInstrumentDeRegistration'] == 1) {
      $enableInstrumentDeRegistration = (int)$this->configuration['enableInstrumentDeRegistration'];
      $hideSavedInstruments = (int)$this->configuration['hideSavedInstruments'];
    } else {
      $hideSavedInstruments = 0;
      $enableInstrumentDeRegistration = 0;
    }
    $payment_order_mode_raw =   $this->configuration['payment_mode_order'];
    
    
    if ($this->configuration['merchant_logo_url'] && @getimagesize($this->configuration['merchant_logo_url'])) {
      $merchant_logo_url = $this->configuration['merchant_logo_url'];
    } else {
      $merchant_logo_url = 'https://www.paynimo.com/CompanyDocs/company-logo-md.png';
    }


    if ($this->configuration['PRIMARY_COLOR_CODE']) {
      $PRIMARY_COLOR_CODE = $this->configuration['PRIMARY_COLOR_CODE'];
    } else {
      $PRIMARY_COLOR_CODE = '#3977b7';
    }

    if ($this->configuration['SECONDARY_COLOR_CODE']) {
      $SECONDARY_COLOR_CODE = $this->configuration['SECONDARY_COLOR_CODE'];
    } else {
      $SECONDARY_COLOR_CODE = '#FFFFFF';
    }

    if ($this->configuration['BUTTON_COLOR_CODE_1']) {
      $BUTTON_COLOR_CODE_1 = $this->configuration['BUTTON_COLOR_CODE_1'];
    } else {
      $BUTTON_COLOR_CODE_1 = '#1969bb';
    }

    if ($this->configuration['BUTTON_COLOR_CODE_2']) {
      $BUTTON_COLOR_CODE_2 = $this->configuration['BUTTON_COLOR_CODE_2'];
    } else {
      $BUTTON_COLOR_CODE_2 = '#FFFFFF';
    }

    $datastring = $this->configuration['worldline_merchant_code'] . "|" . $merchantTxnRefNumber . "|" . $amount . "|" . "|" . "cons" . $cusid_raw . "|" . $customerMobNumber . "|" . Unicode::substr($order->getEmail(), 0, 64) . "||||||||||" . $this->configuration['worldline_SALT'];
    $hashed = hash('sha512', $datastring);
    $data['token'] = $hashed;
    $data = array(
      'mrctCode' => $this->configuration['worldline_merchant_code'],
      'Amount' => $amount,
      'merchantTxnRefNumber' => $merchantTxnRefNumber,
      'CustomerId' => 'cons' . $cusid_raw,
      'customerMobNumber' => $customerMobNumber,
      'email' => Unicode::substr($order->getEmail(), 0, 64),
      'SALT' => $this->configuration['worldline_SALT'],
      'returnUrl' => $returnUrl,
      'scheme' => $this->configuration['worldline_merchant_scheme_code'],
      'currency' => $order->getCurrency(),
      'CustomerName' =>  Unicode::substr($address->first_name . ' ' . $address->last_name, 0, 128),
      'worldline_payment_mode' => $this->configuration['worldline_payment_mode'],
      'enableNewWindowFlow' => $this->configuration['enableNewWindowFlow'],
      'enableExpressPay' => $this->configuration['enableExpressPay'],
      'enableInstrumentDeRegistration' => $enableInstrumentDeRegistration,
      'hideSavedInstruments' => $hideSavedInstruments,
      'separateCardMode' => $this->configuration['separateCardMode'],
      'enableMerTxnDetails' => $this->configuration['enableMerTxnDetails'],
      'saveInstrument' => $this->configuration['saveInstrument'],
      'checkout_url' => '',
      'txnType' => $this->configuration['txnType'],
      'merchantMsg' => $this->configuration['merchantMsg'],
      'disclaimerMsg' => $this->configuration['disclaimerMsg'],
      'checkoutElement' => $this->configuration['checkoutElement'],
      'payment_order_mode_raw' => $payment_order_mode_raw,
      'PRIMARY_COLOR_CODE' => $PRIMARY_COLOR_CODE,
      'merchant_logo_url' => $merchant_logo_url,
      'SECONDARY_COLOR_CODE' => $SECONDARY_COLOR_CODE,
      'BUTTON_COLOR_CODE_1' => $BUTTON_COLOR_CODE_1,
      'BUTTON_COLOR_CODE_2' => $BUTTON_COLOR_CODE_2,
      'orderId' => $order->id(),
      'token' => $hashed,
    );

    $i = 0;
    foreach ($order->products as $product) {
      $i++;
      $data['li_' . $i . '_type'] = 'product';
      $data['li_' . $i . '_name'] = $product->title->value; // @todo: HTML escape and limit to 128 chars
      $data['li_' . $i . '_quantity'] = $product->qty->value;
      $data['li_' . $i . '_product_id'] = $product->model->value;
      $data['li_' . $i . '_price'] = uc_currency_format($product->price->value, FALSE, FALSE, '.');
    }
    foreach ($data as $key => $value) {
      $form[$key] = [
        '#type' => 'hidden',
        '#value' => $value,
        // Ensure the correct keys by sending values from the form root.
        '#parents' => [$key],
      ];
    }

    $form['#action'] = Url::fromRoute('uc_worldline.process_payment')->toString();
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit order'),
    );
    return $form;
  }
}
