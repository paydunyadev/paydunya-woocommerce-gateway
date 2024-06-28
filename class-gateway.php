<?php


if (!defined('ABSPATH')) {
  exit;
}


  
  class WC_Paydunya extends WC_Payment_Gateway {

    private $SUCCESS_CALLBACK_URL = "paydunya_payment_success";
    
    public function __construct() {
      $this->paydunya_errors = new WP_Error();
      
      $this->id = 'paydunya';
      $this->medthod_title = 'PAYDUNYA';
      $this->icon = apply_filters('woocommerce_paydunya_icon', plugins_url('assets/images/logo.png', __FILE__));
      $this->has_fields = false;
      
      $this->init_form_fields();
      $this->init_settings();
      
      $this->title = $this->get_option('title');
      $this->description = $this->get_option('description');
      
      $this->live_master_key = $this->get_option('master_key');
      
      $this->live_private_key = $this->get_option('live_private_key');
      $this->live_token = $this->get_option('live_token');
      
      $this->test_private_key = $this->get_option('test_private_key');
      $this->test_token = $this->get_option('test_token');
      
      $this->sandbox = $this->get_option('sandbox');
      
      $this->sms = $this->get_option('sms');
      $this->sms_url = $this->get_option('sms_url');
      $this->sms_message = $this->get_option('sms_message');
      
      if ($this->get_option('sandbox') == "yes") {
        $this->posturl = 'https://app.paydunya.com/sandbox-api/v1/checkout-invoice/create';
        $this->geturl = 'https://app.paydunya.com/sandbox-api/v1/checkout-invoice/confirm/';
      } else {
        $this->posturl = 'https://app.paydunya.com/api/v1/checkout-invoice/create';
        $this->geturl = 'https://app.paydunya.com/api/v1/checkout-invoice/confirm/';
      }
      
      $this->msg['message'] = "";
      $this->msg['class'] = "";
      
      if (isset($_REQUEST["paydunya"])) {
        wc_add_notice($_REQUEST["paydunya"], "error");
      }
      
      if (isset($_REQUEST["token"]) && $_REQUEST["token"] <> "") {
        $token = trim($_REQUEST["token"]);
        $this->check_paydunya_response($token);
      } else {
        $query_str = $_SERVER['QUERY_STRING'];
        $query_str_arr = explode("?", $query_str);
        foreach ($query_str_arr as $value) {
          $data = explode("=", $value);
          if (trim($data[0]) == "token") {
            $token = isset($data[1]) ? trim($data[1]) : "";
            if ($token <> "") {
              $this->check_paydunya_response($token);
            }
            break;
          }
        }
      }
      
      if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
      } else {
        add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
      }
      add_action( 'woocommerce_api_'. strtolower( get_class($this) ), array( $this, 'callback_handler'));
    }

    function sendsms($number, $message) {
      // Use WordPress's built-in `add_query_arg` function to replace placeholders
      $url = add_query_arg(
          array(
              'NUMBER' => urlencode($number),
              'MESSAGE' => urlencode($message)
          ),
          $this->sms_url
      );
  
      // Remove any remaining "amp;" from the URL
      $url = str_replace("amp;", "&", $url);
  
      // Use WordPress's `wp_remote_get` function instead of curl
      if (!empty(trim($url))) {
          $response = wp_remote_get($url);
          // Check for errors
          if (is_wp_error($response)) {
              error_log($response->get_error_message());
          }
      }
  }
    // Remplacement de l'accès direct à la propriété par la get_description()méthode, 
    // qui est la méthode recommandée pour accéder à la description dans WooCommerce 3.0 et versions ultérieures.
    // Remplacé wptexturizepar wp_kses_post, qui permet davantage de balises HTML 
    // et constitue un moyen plus sûr de nettoyer le texte.
    // Conservé wpautoppour formater le texte, mais il est maintenant appliqué après wp_kses_postpour 
    // garantir que le texte est correctement nettoyé avant le formatage.

    function init_form_fields() {
      $this->form_fields = array(
        'enabled' => array(
          'title' => __('Activer/Désactiver', 'paydunya'),
          'type' => 'checkbox',
          'label' => __('Activer le module de paiement PAYDUNYA.', 'paydunya'),
          'default' => 'no'
        ),
        'title' => array(
          'title' => __('Titre:', 'paydunya'),
          'type' => 'text',
          'description' => __('Texte que verra le client lors du paiement de sa commande.', 'paydunya'),
          'default' => __('Paiement via Mobile Money ou Cartes Bancaires.', 'paydunya')
        ),
        'description' => array(
          'title' => __('Description:', 'paydunya'),
          'type' => 'textarea',
          'description' => __('Description que verra le client lors du paiement de sa commande.', 'paydunya'),
          'default' => __('<h1>PAYDUNYA est la passerelle de paiement la plus populaire pour les achats en ligne au Sénégal.</h1>', 'paydunya')
        ),
        'master_key' => array(
          'title' => __('Clé Principale', 'paydunya'),
          'type' => 'text',
          'description' => __('Clé principale fournie par PAYDUNYA lors de la création de votre application.')
        ),
        'live_private_key' => array(
          'title' => __('Clé Privée de production', 'paydunya'),
          'type' => 'text',
          'description' => __('Clé Privée de production fournie par PAYDUNYA lors de la création de votre application.')
        ),
        'live_token' => array(
          'title' => __('Token de production', 'paydunya'),
          'type' => 'text',
          'description' => __('Token de production fourni par PAYDUNYA lors de la création de votre application.')
        ),
        'test_private_key' => array(
          'title' => __('Clé Privée de test', 'paydunya'),
          'type' => 'text',
          'description' => __('Clé Privée de test fournie par PAYDUNYA lors de la création de votre application.')
        ),
        'test_token' => array(
          'title' => __('Token de test', 'paydunya'),
          'type' => 'text',
          'description' => __('Token de test fourni par PAYDUNYA lors de la création de votre application.')
        ),
        'sandbox' => array(
          'title' => __('Activer le mode test', 'paydunya'),
          'type' => 'checkbox',
          'description' => __("Cocher cette case si vous êtes encore à l'etape des paiements tests.", 'paydunya')
        ),
        'sms' => array(
          'title' => __('Notification SMS', 'paydunya'),
          'type' => 'checkbox',
          'default' => 'no',
          'description' => __("Activer l'envoi de notification par SMS en cas de succès de paiement sur PAYDUNYA.", 'paydunya')
        ),
        'sms_url' => array(
          'title' => __("URL de votre API REST d'envoi de SMS"),
          'type' => 'text',
          'description' => __('Utilisez {NUMBER} pour indiquer le numéro du client et {MESSAGE} pour le message.')
        ),
        'sms_message' => array(
          'title' => __('Contenu du SMS envoyé en cas de succès de paiement'),
          'type' => 'textarea',
          'description' => __("Utilisez {ORDER-ID} pour indiquer l'identifiant de commande, {AMOUNT} pour le montant et {CUSTOMER} pour le nom du client.")
        )
      );
    }
    
    public function admin_options() {
      echo '<h3>'. __('Passerelle de paiement PAYDUNYA', 'paydunya'). '</h3>';
      echo '<p>'. __('PAYDUNYA est la passerelle de paiement la plus populaire pour les achats en ligne au Sénégal.'). '</p>';
      echo '<table class="form-table">';
      // Generate the HTML For the settings form.
      $this->generate_settings_html();
      echo '</table>';
      wp_enqueue_script('paydunya_admin_option_js', plugin_dir_url(__FILE__). 'assets/js/settings.js', array('jquery'), '1.0.1');
  }
        
    function payment_fields() {
      // Since WooCommerce 3.0, we should use the `WC_Payment_Gateway` method `get_description()` instead of accessing the property directly
      if ( $this->get_description() ) {
          // Use `wp_kses_post` instead of `wptexturize` to allow for more HTML tags
          // and `wpautop` to format the text
          echo wpautop( wp_kses_post( $this->get_description() ) );
      }
  }
    
  
    // get_paydunya_argsfonction :

    // Utilisez WC_Order à la place de new WC_Order pour obtenir l'objet de commande.
    // Utilisez wc_get_order à la place de new WC_Order pour obtenir l'objet de commande.
    // Mettez à jour le redirect_url pour l'utiliser wc_get_checkout_urlà la place de get_checkout_url.
    // Mettez à jour le productinfo à utiliser wc_get_order_item_meta au lieu d’accéder directement à l’objet de commande.

    protected function get_paydunya_args($order) {
      global $woocommerce;
  
      $txnid = $order->get_id(). '_'. date("ymds");
  
      $redirect_url = wc_get_checkout_url();
  
      $productinfo = "Commande: ". $order->get_id();
  
      $orderIdString = '?orderId='. $order->get_id();
  
      $str = "$this->merchant_id|$txnid|$order->get_total()|$productinfo|$order->get_billing_first_name()|$order->get_billing_email|||||||||||$this->salt";
      $hash = hash('sha512', $str);
  
      WC()->session->set('paydunya_wc_hash_key', $hash);
  
      $items = $order->get_items();
      $paydunya_items = array();
      foreach ($items as $item) {
          $paydunya_items[] = array(
              "name" => wc_get_order_item_meta($item->get_id(), '_name'),
              "quantity" => $item->get_quantity(),
              "unit_price" => $item->get_subtotal() / (($item->get_quantity() == 0)? 1 : $item->get_quantity()),
              "total_price" => $item->get_subtotal(),
              "description" => ""
          );
      }
      $paydunya_args = array(
          "invoice" => array(
              "items" => $paydunya_items,
              "total_amount" => $order->get_total(),
              "description" => "Paiement de ". $order->get_total(). " FCFA pour article(s) achetés sur ". get_bloginfo("name")
          ),
          "store" => array(
              "name" => get_bloginfo("name"),
              "website_url" => get_site_url()
          ),
          "actions" => array(
              "cancel_url" => $redirect_url,
              "callback_url" => get_site_url().'/?wc-api=WC_Paydunya',
              "return_url" => $redirect_url
          ),
          "custom_data" => array(
              "order_id" => $order->get_id(),
              "trans_id" => $txnid,
              "hash" => $hash
          )
      );
  
      apply_filters('woocommerce_paydunya_args', $paydunya_args, $order);
      return $paydunya_args;
  }
    

    // post_to_urlfonction :

    // Mettez à jour le curl_setoptpour l'utiliser CURL_HTTP_VERSION_1_1 à la place de CURL_HTTP_VERSION_1_0.
    // Mettez à jour le curl_setoptpour l'utiliser CURLOPT_RETURNTRANSFER à la place de CURLOPT_NOBODY.

    function post_to_url($url, $data, $order_id) {
      $json = json_encode($data);
  
      $ch = curl_init();
  
      $master_key = $this->live_master_key;
      $private_key = "";
      $token = "";
  
      if ($this->settings['sandbox'] == "yes") {
          $private_key = $this->test_private_key;
          $token = $this->test_token;
      } else {
          $private_key = $this->live_private_key;
          $token = $this->live_token;
      }
  
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "PAYDUNYA-MASTER-KEY: $master_key",
          "PAYDUNYA-PRIVATE-KEY: $private_key",
          "PAYDUNYA-TOKEN: $token"
      ));
  
      $response = curl_exec($ch);
      $response_decoded = json_decode($response);
  
      WC()->session->set('paydunya_wc_oder_id', $order_id);
  
      if ($response_decoded->response_code && $response_decoded->response_code == "00") {
          $order = wc_get_order($order_id);
          $order->add_order_note("PAYDUNYA Token: ". $response_decoded->token);
          return $response_decoded->response_text;
      } else {
          global $woocommerce;
          $url = wc_get_checkout_url();
          if (strstr($url, "?")) {
              return $url. "&paydunya=". $response_decoded->response_text;
          } else {
              return $url. "?paydunya=". $response_decoded->response_text;
          }
      }
  }
    

    // process_paymentfonction :

    // Mettez à jour l' returninstruction pour l'utiliser wc_get_checkout_urlà la place de get_checkout_url.

    function process_payment($order_id) {
      $order = wc_get_order($order_id);
      return array(
          'result' => 'success',
          'redirect' => $this->post_to_url($this->posturl, $this->get_paydunya_args($order), $order_id)
      );
  }
    
    function showMessage($content) {
      return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
    }
    
    function get_pages($title = false, $indent = true) {
      $wp_pages = get_pages('sort_column=menu_order');
      $page_list = array();
      if ($title)
        $page_list[] = $title;
      foreach ($wp_pages as $page) {
        $prefix = '';
        // show indented child pages?
        if ($indent) {
          $has_parent = $page->post_parent;
          while ($has_parent) {
            $prefix .= ' - ';
            $next_page = get_page($has_parent);
            $has_parent = $next_page->post_parent;
          }
        }
        // add to page list array array
        $page_list[$page->ID] = $prefix . $page->post_title;
      }
      return $page_list;
    }
    
   

    //     check_paydunya_responsefonction :

    // Mettez à jour le curl_setoptpour l'utiliser CURL_HTTP_VERSION_1_1à la place de CURL_HTTP_VERSION_1_0.
    // Mettez à jour le curl_setoptpour l'utiliser CURLOPT_RETURNTRANSFERà la place de CURLOPT_NOBODY.
    // Mettez à jour le wc_get_orderpour l'utiliser wc_get_orderà la place de new WC_Order.

    function check_paydunya_response($mtoken) {
      global $woocommerce;
  
      if ($mtoken <> "") {
          $wc_order_id = WC()->session->get('paydunya_wc_oder_id');
          $hash = WC()->session->get('paydunya_wc_hash_key');
          $order = wc_get_order($wc_order_id);
  
          try {
              $ch = curl_init();
              $master_key = $this->live_master_key;
              $private_key = "";
              $url = $this->geturl. $mtoken;
              $token = "";
  
              if ($this->settings['sandbox'] == "yes") {
                  $private_key = $this->test_private_key;
                  $token = $this->test_token;
              } else {
                  $private_key = $this->live_private_key;
                  $token = $this->live_token;
              }
  
              curl_setopt_array($ch, array(
                  CURLOPT_URL => $url,
                  CURLOPT_NOBODY => false,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_SSL_VERIFYPEER => false,
                  CURLOPT_HTTPHEADER => array(
                      "PAYDUNYA-MASTER-KEY: $master_key",
                      "PAYDUNYA-PRIVATE-KEY: $private_key",
                      "PAYDUNYA-TOKEN: $token"
                  ),
              ));
  
              $response = curl_exec($ch);
              $response_decoded = json_decode($response);
  
              $respond_code = $response_decoded->response_code;
  
              if ($respond_code == "00") {
                  //payment found
                  $status = $response_decoded->status;
                  $custom_data = $response_decoded->custom_data;
                  $order_id = $custom_data->order_id;
  
                  if ($wc_order_id <> $order_id) {
                      $message = "Votre session de transaction a expiré. Votre numéro de commande est: $order_id";
                      $message_type = "notice";
                      $order->add_order_note($message);
                      $redirect_url = $order->get_cancel_order_url();
                  }
  
                  if ($status == "completed") {
                      //payment was completely processed
                      $total_amount = strip_tags(wc_price($order->get_total()));
                      $message = "Merci pour votre achat. La transaction a été un succès, le paiement a été reçu. Votre commande est en cours de traitement. Votre numéro de commande est $order_id";
                      $message_type = "success";
                      $order->payment_complete();
                      $order->update_status('completed');
                      $order->add_order_note('Paiement PAYDUNYA effectué avec succès<br/>ID unique reçu de PAYDUNYA: '. $mtoken);
                      $order->add_order_note($this->msg['message']);
                      $woocommerce->cart->empty_cart();
                      $redirect_url = $this->get_return_url($order);
                      $customer = trim($order->get_billing_last_name(). " ". $order->get_billing_first_name());
  
                      if ($this->sms == "yes") {
                          $phone_no = get_user_meta(get_current_user_id(), 'billing_phone', true);
                          $sms = $this->sms_message;
                          $sms = str_replace("{ORDER-ID}", $order_id, $sms);
                          $sms = str_replace("{AMOUNT}", $total_amount, $sms);
                          $sms = str_replace("{CUSTOMER}", $customer, $sms);
                          $this->sendsms($phone_no, $sms);
                      }
                  } else {
                      //payment is still pending, or user cancelled request
                      $message = "La transaction n'a pu être complétée.";
                      $message_type = "error";
                      $order->add_order_note("La transaction a échoué ou l'utilisateur a eu à faire demande d'annulation de paiement");
                      $redirect_url = $order->get_cancel_order_url();
                  }
              } else {
                  //payment not found
                  $message = "Merci de nous avoir choisi. Malheureusement, la transaction a été refusée.";
                  $message_type = "error";
                  $redirect_url = $order->get_cancel_order_url();
              }
  
              $notification_message = array(
                  'essage' => $message,
                  'essage_type' => $message_type
              );
  
              if (version_compare(WOOCOMMERCE_VERSION, "2.2") >= 0) {
                  add_post_meta($wc_order_id, '_paydunya_hash', $hash, true);
              }
  
              update_post_meta($wc_order_id, '_paydunya_wc_message', $notification_message);
  
              WC()->session->__unset('paydunya_wc_hash_key');
              WC()->session->__unset('paydunya_wc_order_id');
  
              wp_redirect($redirect_url);
              exit;
          } catch (Exception $e) {
              $order->add_order_note('Erreur: '. $e->getMessage());
  
              $redirect_url = $order->get_cancel_order_url();
              wp_redirect($redirect_url);
              exit;
          }
      }
  }
    
  

    //     callback_handlerfonction :

    // Mettez à jour le wc_get_orderpour l'utiliser wc_get_orderà la place de new WC_Order.
    // Mettez à jour le wc_reduce_stock_levelspour l'utiliser wc_update_product_stockà la place.


    public function callback_handler() {
      try {
          if ($_POST['data']['hash'] === hash('sha512', $this->live_master_key)) {
  
              if ($_POST['data']['status'] == "completed") {
                  $order = wc_get_order($_POST['data']['custom_data']['order_id']);
                  $order->payment_complete();
                  $order->update_status('completed');
                  $order->add_order_note($this->msg['message']);
                  // wc_update_product_stock($order);
              }
  
          } else {
              die("Cette requête n'a pas été émise par PayDunya");
          }
      } catch (Exception $e) {
          die();
      }
  
      die();
  }

    // Add FCFA currency
    public static function add_paydunya_fcfa_currency($currencies) {
  // Use the `woocommerce_currencies` filter to add the FCFA currency
  $currencies['FCFA'] = __('BCEAO XOF', 'woocommerce');
  return $currencies;
}


// Add FCFA currency symbol
    public static function add_paydunya_fcfa_currency_symbol($currency_symbols) {
  // Use the `woocommerce_currency_symbols` filter to add the FCFA currency symbol
  $currency_symbols['FCFA'] = 'FCFA';
  return $currency_symbols;
}


// // Add Paydunya gateway
// public static function woocommerce_add_paydunya_gateway($gateways) {
//   // Use the `woocommerce_payment_gateways` filter to add the Paydunya gateway
//   $gateways[] = new WC_Paydunya(); // Instantiate the gateway class
//   return $gateways;
// }


// Add settings link on plugin page
//     public static function woocommerce_add_paydunya_settings_link($links) {
//   // Use the `plugin_action_links` filter to add a settings link
//   $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_paydunya') . '">Paramètres</a>';
//   array_unshift($links, $settings_link);
//   return $links;
// }

    
}
  
  $plugin = plugin_basename(__FILE__);

  add_filter('woocommerce_currencies', array('WC_Paydunya', 'add_paydunya_fcfa_currency'));
  add_filter('woocommerce_currency_symbols', array('WC_Paydunya', 'add_paydunya_fcfa_currency_symbol'), 10, 1);
  
  // add_filter("plugin_action_links_$plugin", array('WC_Paydunya', 'woocommerce_add_paydunya_settings_link'));
  // add_filter('woocommerce_payment_gateways', array('WC_Paydunya', 'woocommerce_add_paydunya_gateway'));
