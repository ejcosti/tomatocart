<?php

/*
  $Id: payhub.php $
  TomatoCart Open Source Shopping Cart Solutions
  http://www.tomatocart.com

  Copyright (c) 2009 Wuxi Elootec Technology Co., Ltd;  Copyright (c) 2006 osCommerce

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v2 (1991)
  as published by the Free Software Foundation.
*/

  class osC_Payment_payhub extends osC_Payment {
    var $_title,
        $_code = 'payhub',
        $_status = false,
        $_sort_order,
        $_order_status;
        
    // class constructor
    function osC_Payment_payhub() {
      global $osC_Database, $osC_Language, $osC_ShoppingCart;

      $this->_title = $osC_Language->get('payment_payhub_title');
      $this->_method_title = $osC_Language->get('payment_payhub_method_title');
      $this->_sort_order = MODULE_PAYMENT_PAYHUB_SORT_ORDER;
      $this->_status = ((MODULE_PAYMENT_PAYHUB_STATUS == '1') ? true : false);
      
      $this->_order_status = (int)MODULE_PAYMENT_PAYHUB_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_PAYHUB_ORDER_STATUS_ID : (int)ORDERS_STATUS_PAID;

      if ($this->_status === true) {
        if ((int)MODULE_PAYMENT_PAYHUB_ZONE > 0) {
          $check_flag = false;

          $Qcheck = $osC_Database->query('select zone_id from :table_zones_to_geo_zones where geo_zone_id = :geo_zone_id and zone_country_id = :zone_country_id order by zone_id');
          $Qcheck->bindTable(':table_zones_to_geo_zones', TABLE_ZONES_TO_GEO_ZONES);
          $Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_PAYHUB_ZONE);
          $Qcheck->bindInt(':zone_country_id', $osC_ShoppingCart->getBillingAddress('country_id'));
          $Qcheck->execute();

          while ($Qcheck->next()) {
            if ($Qcheck->valueInt('zone_id') < 1) {
              $check_flag = true;
              break;
            } elseif ($Qcheck->valueInt('zone_id') == $osC_ShoppingCart->getBillingAddress('zone_id')) {
              $check_flag = true;
              break;
            }
          }

          if ($check_flag == false) {
            $this->_status = false;
          }
        }
      }
    }
    
    function getJavascriptBlock() {
      global $osC_Language, $osC_CreditCard;

      $osC_CreditCard = new osC_CreditCard();

      $js = '  if (payment_value == "' . $this->_code . '") {' . "\n" .
            '    var payhub_cc_owner = document.checkout_payment.payhub_cc_owner.value;' . "\n" .
            '    var payhub_cc_number = document.checkout_payment.payhub_cc_number.value;' . "\n" .
            '    payhub_cc_number = payhub_cc_number.replace(/[^\d]/gi, "");' . "\n";

      if (MODULE_PAYMENT_PAYHUB_VERIFY_WITH_CVC == '1') {
        $js .= '    var payhub_cvc = document.checkout_payment.payhub_cc_cvc.value;' . "\n";
      }

      if (CFG_CREDIT_CARDS_VERIFY_WITH_JS == '1') {
        $js .= '    var payhub_cc_type_match = false;' . "\n";
      }

      $js .= '    if (payhub_cc_owner == "" || payhub_cc_owner.length < ' . CC_OWNER_MIN_LENGTH . ') {' . "\n" .
             '      error_message = error_message + "' . sprintf($osC_Language->get('payment_payhub_js_credit_card_owner'), CC_OWNER_MIN_LENGTH) . '\n";' . "\n" .
             '      error = 1;' . "\n" .
             '    }' . "\n";

      $has_type_patterns = false;

      if ( (CFG_CREDIT_CARDS_VERIFY_WITH_JS == '1') && (osc_empty(MODULE_PAYMENT_PAYHUB_ACCEPTED_TYPES) === false) ) {
        foreach (explode(',', MODULE_PAYMENT_PAYHUB_ACCEPTED_TYPES) as $type_id) {
          if ($osC_CreditCard->typeExists($type_id)) {
            $has_type_patterns = true;

            $js .= '    if ( (payhub_cc_type_match == false) && (payhub_cc_number.match(' . $osC_CreditCard->getTypePattern($type_id) . ') != null) ) { ' . "\n" .
                   '      payhub_cc_type_match = true;' . "\n" .
                   '    }' . "\n";
          }
        }
      }

      if ($has_type_patterns === true) {
        $js .= '    if ((payhub_cc_type_match == false) || (mod10(payhub_cc_number) == false)) {' . "\n" .
               '      error_message = error_message + "' . $osC_Language->get('payment_payhub_js_credit_card_not_accepted') . '\n";' . "\n" .
               '      error = 1;' . "\n" .
               '    }' . "\n";
      } else {
        $js .= '    if (payhub_cc_number == "" || payhub_cc_number.length < ' . CC_NUMBER_MIN_LENGTH . ') {' . "\n" .
               '      error_message = error_message + "' . sprintf($osC_Language->get('payment_payhub_js_credit_card_number'), CC_NUMBER_MIN_LENGTH) . '\n";' . "\n" .
               '      error = 1;' . "\n" .
               '    }' . "\n";
      }

      if (MODULE_PAYMENT_PAYHUB_VERIFY_WITH_CVC == '1') {
        $js .= '    if (payhub_cc_cvc == "" || payhub_cc_cvc.length < 3) {' . "\n" .
               '      error_message = error_message + "' . sprintf($osC_Language->get('payment_payhub_js_credit_card_cvc'), 3) . '\n";' . "\n" .
               '      error = 1;' . "\n" .
               '    }' . "\n";
      }

      $js .= '  }' . "\n";

      return $js;
    }
    
    function selection() {
      global $osC_Database, $osC_Language, $osC_ShoppingCart;

      for ($i=1; $i<13; $i++) {
        $expires_month[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B',mktime(0,0,0,$i,1)));
      }

      $year = date('Y');
      for ($i=$year; $i < $year+10; $i++) {
        $expires_year[] = array('id' => $i, 'text' => strftime('%Y',mktime(0,0,0,1,1,$i)));
      }

      $selection = array('id' => $this->_code,
                         'module' => $this->_method_title,
                         'fields' => array(array('title' => $osC_Language->get('payment_payhub_credit_card_owner'),
                                                 'field' => osc_draw_input_field('payhub_cc_owner', $osC_ShoppingCart->getBillingAddress('firstname') . ' ' . $osC_ShoppingCart->getBillingAddress('lastname'), 'style="margin:5px 0;"')),
                                           array('title' => $osC_Language->get('payment_payhub_credit_card_number'),
                                                 'field' => osc_draw_input_field('payhub_cc_number'), '', 'style="margin:5px 0;"'),
                                           array('title' => $osC_Language->get('payment_payhub_credit_card_expires'),
                                                 'field' => osc_draw_pull_down_menu('payhub_cc_expires_month', $expires_month, null, 'style="margin:5px 0;"') . '&nbsp;' . osc_draw_pull_down_menu('payhub_cc_expires_year', $expires_year, null, 'style="margin:5px 0;"'))));

     if (MODULE_PAYMENT_PAYHUB_VERIFY_WITH_CVC == '1') {
       $selection['fields'][] = array('title' => $osC_Language->get('payment_payhub_credit_card_cvc'),
                                      'field' => osc_draw_input_field('payhub_cc_cvc', null, 'size="5" maxlength="4" style="margin:5px 0;"'));
     }

      return $selection;
    }
    
    
    function pre_confirmation_check() {
      $this->_verifyData();
    }
    
    function confirmation() {
      global $osC_Language, $osC_CreditCard;

      $confirmation = array('title' => $this->_method_title,
                            'fields' => array(array('title' => $osC_Language->get('payment_payhub_credit_card_owner'),
                                                    'field' => $osC_CreditCard->getOwner()),
                                              array('title' => $osC_Language->get('payment_payhub_credit_card_number'),
                                                    'field' => $osC_CreditCard->getSafeNumber()),
                                              array('title' => $osC_Language->get('payment_payhub_credit_card_expires'),
                                                    'field' => $osC_CreditCard->getExpiryMonth() . ' / ' . $osC_CreditCard->getExpiryYear())));

      if (MODULE_PAYMENT_PAYHUB_VERIFY_WITH_CVC == '1') {
        $confirmation['fields'][] = array('title' => $osC_Language->get('payment_payhub_credit_card_cvc'),
                                          'field' => $osC_CreditCard->getCVC());
      }

      return $confirmation;
    }
    
    function process_button() {
      global $osC_CreditCard;

      $fields = osc_draw_hidden_field('payhub_cc_owner', $osC_CreditCard->getOwner()) .
                osc_draw_hidden_field('payhub_cc_expires_month', $osC_CreditCard->getExpiryMonth()) .
                osc_draw_hidden_field('payhub_cc_expires_year', $osC_CreditCard->getExpiryYear()) .
                osc_draw_hidden_field('payhub_cc_number', $osC_CreditCard->getNumber());

      if (MODULE_PAYMENT_PAYHUB_VERIFY_WITH_CVC == '1') {
        $fields .= osc_draw_hidden_field('payhub_cc_cvc', $osC_CreditCard->getCVC());
      }

      return $fields;
    }
    
    function process() {
      global $osC_Currencies, $osC_ShoppingCart, $messageStack, $osC_Customer, $osC_Tax, $osC_CreditCard;
      
      $this->_verifyData();
      
      $orders_id = osC_Order::insert();
      
      $params = array('orgid' => MODULE_PAYMENT_PAYHUB_ORGANIZATION_ID,
                      'username' => MODULE_PAYMENT_PAYHUB_API_USERNAME,
                      'password' => MODULE_PAYMENT_PAYHUB_API_PASSWORD,
                      'tid' => MODULE_PAYMENT_PAYHUB_TERMINAL_ID,
                      'x_version' => '3.1', 
                      'x_delim_data' => 'TRUE', 
                      'x_delim_char' => ',', 
                      'x_encap_char' => '"', 
                      'x_relay_response' => 'FALSE', 
                      'x_first_name' => substr($osC_ShoppingCart->getBillingAddress('firstname'), 0, 50), 
                      'x_last_name' => substr($osC_ShoppingCart->getBillingAddress('lastname'), 0, 50), 
                      'x_company' => substr($osC_ShoppingCart->getBillingAddress('company'), 0, 50), 
                      'x_address' => substr($osC_ShoppingCart->getBillingAddress('street_address'), 0, 60), 
                      'x_city' => substr($osC_ShoppingCart->getBillingAddress('city'), 0, 40), 
                      'x_state' => substr($osC_ShoppingCart->getBillingAddress('state'), 0, 40), 
                      'x_zip' => substr($osC_ShoppingCart->getBillingAddress('postcode'), 0, 20), 
                      'x_country' => substr($osC_ShoppingCart->getBillingAddress('country_iso_code_2'), 0, 60), 
                      'x_cust_id' => substr($osC_Customer->getID(), 0, 20), 
                      'x_customer_ip' => osc_get_ip_address(),
                      'x_invoice_num' => $order_id, 
                      'x_email' => substr($osC_Customer->getEmailAddress(), 0, 255), 
                      'x_description' => substr(STORE_NAME, 0, 255), 
                      'x_amount' => substr($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal()), 0, 15), 
                      'x_currency_code' => substr($osC_Currencies->getCode(), 0, 3), 
                      'x_method' => 'CC', 
                      'x_card_num' => $osC_CreditCard->getNumber(), 
                      'x_exp_date' => $osC_CreditCard->getExpiryMonth() . $osC_CreditCard->getExpiryYear(),
                      'mode' => 'live');
                      
      
      if (ACCOUNT_TELEPHONE > -1) {
        $params['x_phone'] = $osC_ShoppingCart->getBillingAddress('telephone_number');
      }
      
      if (MODULE_PAYMENT_PAYHUB_VERIFY_WITH_CVC == '1') {
        $params['x_card_code'] = $osC_CreditCard->getCVC();
      }
      
      if ($osC_ShoppingCart->hasShippingAddress()) {
        $params['x_ship_to_first_name'] = substr($osC_ShoppingCart->getShippingAddress('firstname'), 0, 50);
        $params['x_ship_to_last_name'] = substr($osC_ShoppingCart->getShippingAddress('lastname'), 0, 50);
        $params['x_ship_to_company'] = substr($osC_ShoppingCart->getShippingAddress('company'), 0, 50);
        $params['x_ship_to_address'] = substr($osC_ShoppingCart->getShippingAddress('street_address'), 0, 60);
        $params['x_ship_to_city'] = substr($osC_ShoppingCart->getShippingAddress('city'), 0, 40);
        $params['x_ship_to_state'] = substr($osC_ShoppingCart->getShippingAddress('zone_code'), 0, 40);
        $params['x_ship_to_zip'] = substr($osC_ShoppingCart->getShippingAddress('postcode'), 0, 20);
        $params['x_ship_to_country'] = substr($osC_ShoppingCart->getShippingAddress('country_iso_code_2'), 0, 60);
      }
      
      switch (MODULE_PAYMENT_PAYHUB_TRANSACTION_SERVER) {
        case 'Test':
          var_dump(MODULE_PAYMENT_PAYHUB_TRANSACTION_SERVER);
          $gateway_url = 'https://checkout.payhub.com/invoice/aimprocess';
          $params['mode'] = 'demo';
          break;

        default:
          $gateway_url = 'https://checkout.payhub.com/invoice/aimprocess';
          break;
      }

      $shipping_tax = ($osC_ShoppingCart->getShippingMethod('cost')) * ($osC_Tax->getTaxRate($osC_ShoppingCart->getShippingMethod('tax_class_id'), $osC_ShoppingCart->getTaxingAddress('country_id'), $osC_ShoppingCart->getTaxingAddress('zone_id')) / 100);
      $total_tax = $osC_ShoppingCart->getTax() - $shipping_tax;
      
      if ($total_tax > 0) {
        $params['x_tax'] = $osC_Currencies->formatRaw($total_tax);
      }
      
      $params['x_freight'] = $osC_Currencies->formatRaw($osC_ShoppingCart->getShippingMethod('cost'));
      
      $post_string = '';
      foreach ($params as $key => $value) {
        $post_string .= $key . '=' . urlencode(trim($value)) . '&';
      }
      $post_string = substr($post_string, 0, -1);
      
      if ($osC_ShoppingCart->hasContents()) {
        $i = 1;
        foreach($osC_ShoppingCart->getProducts() as $product) {
          $post_string .= '&x_line_item=' . urlencode($i) . '<|>' . urlencode(substr($product['name'], 0, 31)) . '<|>' . urlencode(substr($product['name'], 0, 255)) . '<|>' . urlencode($product['quantity']) . '<|>' . urlencode($osC_Currencies->formatRaw($product['final_price'])) . '<|>' . urlencode($product['tax_class_id'] > 0 ? 'YES' : 'NO');
          
          $i++;
        }
      }

      
      $transaction_response = $this->sendTransactionToGateway($gateway_url, $post_string);
      $tresponse = preg_split("/\|/", $transaction_response);


      $error = false;
      

      switch ($tresponse[2]) {
        case '00':
          $error = false;
          break;

        default:
          $error = $tresponse[1] . '  Response Code:  ' . $tresponse[2];
          break;
      }

      
      if ($error != false) {
        osC_Order::remove($orders_id);
        
        osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'checkout&error=' . $error, 'SSL'));
      }else {
        osC_Order::process($orders_id, $this->_order_status, $transaction_response);
      }
    }
    
    function get_error() {
      global $osC_Language;
      
      $error = false;
      
      if (isset($_GET['error'])) {
        $error_message = $_GET['error'];       
        $error = array('title' => $osC_Language->get('payment_payhub_error_title'),
                       'error' => $error_message);
      }

      return $error;
    }
    
    function _verifyData() {
      global $osC_Language, $messageStack, $osC_CreditCard;

      $osC_CreditCard = new osC_CreditCard($_POST['payhub_cc_number'], $_POST['payhub_cc_expires_month'], $_POST['payhub_cc_expires_year']);
      $osC_CreditCard->setOwner($_POST['payhub_cc_owner']);

      if (MODULE_PAYMENT_PAYHUB_VERIFY_WITH_CVC == '1') {
        $osC_CreditCard->setCVC($_POST['payhub_cc_cvc']);
      }

      if (($result = $osC_CreditCard->isValid(MODULE_PAYMENT_PAYHUB_ACCEPTED_TYPES)) !== true) {
        $error = '';

        switch ($result) {
          case -2:
            $error = $osC_Language->get('payment_payhub_error_invalid_exp_date');
            break;

          case -3:
            $error = $osC_Language->get('payment_payhub_error_expired');
            break;

          case -5:
            $error = $osC_Language->get('payment_payhub_error_not_accepted');
            break;

          default:
            $error = $osC_Language->get('payment_payhub_error_general');
            break;
        }
        
        if ($messageStack->size('checkout_payment') > 0) {
          $messageStack->reset();
        }

        $messageStack->add_session('checkout_payment', $error, 'error');
      }
    }
  }
?>