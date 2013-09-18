<?php


/**
 * The administration side of the payhub payment module
 */

  class osC_Payment_PAYHUB extends osC_Payment_Admin {

/**
 * The administrative title of the payment module
 *
 * @var string
 * @access private
 */
  var $_title;
  
/**
 * The code of the payment module
 *
 * @var string
 * @access private
 */

  var $_code = 'payhub';
  
/**
 * The developers name
 *
 * @var string
 * @access private
 */

  var $_author_name = 'EJ';
  
/**
 * The developers address
 *
 * @var string
 * @access private
 */  
  
  var $_author_www = 'http://railswebdev.com';
  
/**
 * The status of the module
 *
 * @var boolean
 * @access private
 */

  var $_status = false;
  
/**
 * Constructor
 */

  function osC_Payment_payhub() {
    global $osC_Language;
    
    $this->_title = $osC_Language->get('payment_payhub_title');
    $this->_description = $osC_Language->get('payment_payhub_description');
    $this->_method_title = $osC_Language->get('payment_payhub_method_title');
    $this->_status = (defined('MODULE_PAYMENT_PAYHUB_STATUS') && (MODULE_PAYMENT_PAYHUB_STATUS == '1') ? true : false);
    $this->_sort_order = (defined('MODULE_PAYMENT_PAYHUB_SORT_ORDER') ? MODULE_PAYMENT_PAYHUB_SORT_ORDER : null);
  }
  
/**
 * Checks to see if the module has been installed
 *
 * @access public
 * @return boolean
 */

  function isInstalled() {
    return (bool)defined('MODULE_PAYMENT_PAYHUB_STATUS');
  }
  
/**
 * Installs the module
 *
 * @access public
 * @see osC_Payment_Admin::install()
 */

  function install() {
    global $osC_Database, $osC_Language;
    
    parent::install();
    
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable PayHub payment module', 'MODULE_PAYMENT_PAYHUB_STATUS', '-1', 'Do you want to accept Credit Card Payments through PayHub?', '6', '0', 'osc_cfg_set_boolean_value(array(1, -1))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Server', 'MODULE_PAYMENT_PAYHUB_TRANSACTION_SERVER', 'Live', 'Perform transactions on the Live or Demo server. If you select Demo, please be sure to switch it back to Live before publishing your site.  CVV info:  VISA = 999, AMEX = 9997, DISC = 998, MC = 996', '6', '0', 'osc_cfg_set_boolean_value(array(\'Live\', \'Test\'))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('PayHub Organization ID', 'MODULE_PAYMENT_PAYHUB_ORGANIZATION_ID', '', 'Your Organization ID goes here.', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('API Username', 'MODULE_PAYMENT_PAYHUB_API_USERNAME', '', 'This is your PayHub API Username.', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('API Password', 'MODULE_PAYMENT_PAYHUB_API_PASSWORD', '', 'This is your PayHub API Password.', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Terminal ID', 'MODULE_PAYMENT_PAYHUB_TERMINAL_ID', '', 'This is your API Terminal ID.', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Credit Cards', 'MODULE_PAYMENT_PAYHUB_ACCEPTED_TYPES', '', 'Accept these credit card types for this payment method.', '6', '0', 'osc_cfg_set_credit_cards_checkbox_field', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Verify With CVC', 'MODULE_PAYMENT_PAYHUB_VERIFY_WITH_CVC', '1', 'Verify the credit card with the billing address with the Credit Card Verification Checknumber (CVC)?', '6', '0', 'osc_cfg_use_get_boolean_value', 'osc_cfg_set_boolean_value(array(1, -1))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PAYHUB_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PAYHUB_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'osc_cfg_use_get_zone_class_title', 'osc_cfg_set_zone_classes_pull_down_menu', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PAYHUB_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'osc_cfg_set_order_statuses_pull_down_menu', 'osc_cfg_use_get_order_status_title', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('cURL Program Location', 'MODULE_PAYMENT_PAYHUB_CURL', '/usr/bin/curl', 'The location to the cURL program application', '6', '0', now())");
  }

/**
 * Return the configuration parameter keys in an array
 *
 * @access public
 * @return array
 */

  function getKeys() {
    if (!isset($this->_keys)) {
      $this->_keys = array('MODULE_PAYMENT_PAYHUB_STATUS',
                           'MODULE_PAYMENT_PAYHUB_TRANSACTION_SERVER', 
                           'MODULE_PAYMENT_PAYHUB_ORGANIZATION_ID', 
                           'MODULE_PAYMENT_PAYHUB_API_USERNAME',
                           'MODULE_PAYMENT_PAYHUB_API_PASSWORD',
                           'MODULE_PAYMENT_PAYHUB_TERMINAL_ID', 
                           'MODULE_PAYMENT_PAYHUB_ACCEPTED_TYPES',
                           'MODULE_PAYMENT_PAYHUB_VERIFY_WITH_CVC', 
                           'MODULE_PAYMENT_PAYHUB_ZONE', 
                           'MODULE_PAYMENT_PAYHUB_ORDER_STATUS_ID', 
                           'MODULE_PAYMENT_PAYHUB_SORT_ORDER', 
                           'MODULE_PAYMENT_PAYHUB_CURL');
    }
  
    return $this->_keys;
 } 
}
?>

