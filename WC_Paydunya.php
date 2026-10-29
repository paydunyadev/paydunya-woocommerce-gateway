<?php

if (!defined('ABSPATH')) {
  exit;
}

class WC_Paydunya extends WC_Payment_Gateway
{

  // Accepted currency: "BCEAO XOF" (CFA franc code), added to WooCommerce by this plugin (see custom-gateway.php).
  const SUPPORTED_CURRENCIES = array('FCFA');

  private $live_master_key;
  private $live_private_key;
  private $live_token;
  private $test_private_key;
  private $test_token;
  private $sandbox;
  private $sms;
  private $sms_url;
  private $sms_message;
  private $posturl;
  private $geturl;

  public function __construct()
  {
    $this->id = 'paydunya';
    $this->method_title = 'PAYDUNYA';
    $this->method_description = __('Paiement par Mobile Money et cartes bancaires via PAYDUNYA (zone UEMOA). Le client est redirigé vers la page de paiement PAYDUNYA.', 'paydunya');
    $this->icon = apply_filters('woocommerce_paydunya_icon', plugins_url('assets/images/logo.png', __FILE__));
    $this->has_fields = false;
    $this->supports = array('products');

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

    if ($this->sandbox == "yes") {
      $this->posturl = 'https://app.paydunya.com/sandbox-api/v1/checkout-invoice/create';
      $this->geturl = 'https://app.paydunya.com/sandbox-api/v1/checkout-invoice/confirm/';
    } else {
      $this->posturl = 'https://app.paydunya.com/api/v1/checkout-invoice/create';
      $this->geturl = 'https://app.paydunya.com/api/v1/checkout-invoice/confirm/';
    }

    // The customer's return from PayDunya is handled by handle_return() (see custom-gateway.php)
    // and the server notification is handled by callback_handler().
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'callback_handler'));
  }

  function init_form_fields()
  {
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
        'default' => __('<h1>PAYDUNYA est la plateforme qui facilite le paiement pour l\'achat de biens et services via mobile money et cartes bancaires.</h1>', 'paydunya')
      ),
      'master_key' => array(
        'title' => __('Clé Principale', 'paydunya'),
        'type' => 'paydunya_secret',
        'description' => __('Clé principale fournie par PAYDUNYA lors de la création de votre application.', 'paydunya')
      ),
      'live_private_key' => array(
        'title' => __('Clé Privée de production', 'paydunya'),
        'type' => 'paydunya_secret',
        'description' => __('Clé Privée de production fournie par PAYDUNYA lors de la création de votre application.', 'paydunya')
      ),
      'live_token' => array(
        'title' => __('Token de production', 'paydunya'),
        'type' => 'paydunya_secret',
        'description' => __('Token de production fourni par PAYDUNYA lors de la création de votre application.', 'paydunya')
      ),
      'test_private_key' => array(
        'title' => __('Clé Privée de test', 'paydunya'),
        'type' => 'paydunya_secret',
        'description' => __('Clé Privée de test fournie par PAYDUNYA lors de la création de votre application.', 'paydunya')
      ),
      'test_token' => array(
        'title' => __('Token de test', 'paydunya'),
        'type' => 'paydunya_secret',
        'description' => __('Token de test fourni par PAYDUNYA lors de la création de votre application.', 'paydunya')
      ),
      'sandbox' => array(
        'title' => __('Activer le mode test', 'paydunya'),
        'type' => 'checkbox',
        'default' => 'no',
        'description' => __("Cocher cette case si vous êtes encore à l'etape des paiements tests.", 'paydunya')
      ),
      'sms' => array(
        'title' => __('Notification SMS', 'paydunya'),
        'type' => 'checkbox',
        'default' => 'no',
        'description' => __("Activer l'envoi de notification par SMS en cas de succès de paiement sur PAYDUNYA.", 'paydunya')
      ),
      'sms_url' => array(
        'title' => __("URL de votre API REST d'envoi de SMS", 'paydunya'),
        'type' => 'text',
        'description' => __('Utilisez {NUMBER} pour indiquer le numéro du client et {MESSAGE} pour le message.', 'paydunya')
      ),
      'sms_message' => array(
        'title' => __('Contenu du SMS envoyé en cas de succès de paiement', 'paydunya'),
        'type' => 'textarea',
        'description' => __("Utilisez {ORDER-ID} pour indiquer l'identifiant de commande, {AMOUNT} pour le montant et {CUSTOMER} pour le nom du client.", 'paydunya')
      )
    );
  }

  // API keys and tokens field: the saved value is never returned to the page
  // (neither visible nor in the source code). A saved key is represented by asterisks.
  public function generate_paydunya_secret_html($key, $data)
  {
    $field_key = $this->get_field_key($key);
    $data = wp_parse_args($data, array('title' => '', 'description' => '', 'desc_tip' => false));
    $is_set = $this->get_option($key) !== '';
    if ($is_set) {
      $data['description'] .= ' ' . __('Une clé est enregistrée : laissez le champ vide pour la conserver, ou saisissez une nouvelle valeur pour la remplacer.', 'paydunya');
    }

    ob_start();
?>
    <tr valign="top">
      <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); ?></label>
      </th>
      <td class="forminp">
        <fieldset>
          <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
          <input class="input-text regular-input" type="password" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" value="" placeholder="<?php echo $is_set ? '********************' : ''; ?>" autocomplete="new-password" />
          <?php echo $this->get_description_html($data); ?>
        </fieldset>
      </td>
    </tr>
<?php
    return ob_get_clean();
  }

  // If the field is left empty when saving, the previously saved key is retained.
  public function validate_paydunya_secret_field($key, $value)
  {
    $value = is_string($value) ? sanitize_text_field(trim(stripslashes($value))) : '';
    return $value === '' ? $this->get_option($key) : $value;
  }

  // The payment gateway is only offered to customers if the store uses BCEAO XOF (CFA francs).
  public function is_available()
  {
    return parent::is_available() && self::is_currency_supported(get_woocommerce_currency());
  }

  public static function is_currency_supported($currency)
  {
    return in_array($currency, self::SUPPORTED_CURRENCIES, true);
  }

  function payment_fields()
  {
    if ($this->get_description()) {
      echo wpautop(wp_kses_post($this->get_description()));
    }
  }

  function process_payment($order_id)
  {
    $order = wc_get_order($order_id);

    if (!self::is_currency_supported($order->get_currency())) {
      wc_add_notice(__('Veuillez choisir la devise BCEAO XOF (FCFA) -- FCFA) pour continuer.', 'paydunya'), 'error');
      return array('result' => 'failure');
    }

    $checkout_url = $this->create_invoice($order);
    if (is_wp_error($checkout_url)) {
      wc_add_notice($checkout_url->get_error_message(), 'error');
      return array('result' => 'failure');
    }

    return array(
      'result' => 'success',
      'redirect' => $checkout_url
    );
  }

  protected function get_paydunya_args($order)
  {
    // Return URL handled by handle_return(). It contains the order ID and order key, allowing us to retrieve
    // the order without relying on the customer's session. home_url() works both locally and online.
    // The key is placed at the end because PayDunya may append "?token=..." to it (see get_returned_token()).
    $return_url = add_query_arg(
      array(
        'paydunya_return' => $order->get_id(),
        'key' => $order->get_order_key()
      ),
      home_url('/')
    );
    $cancel_url = add_query_arg('paydunya_cancel', '1', $return_url);

    $paydunya_items = array();
    foreach ($order->get_items() as $item) {
      $quantity = max(1, $item->get_quantity());
      $paydunya_items[] = array(
        "name" => $item->get_name(),
        "quantity" => $item->get_quantity(),
        "unit_price" => $item->get_subtotal() / $quantity,
        "total_price" => $item->get_subtotal(),
        "description" => ""
      );
    }

    $paydunya_args = array(
      "invoice" => array(
        "items" => $paydunya_items,
        "customer" => array(
          "name" => trim($order->get_billing_first_name() . " " . $order->get_billing_last_name()),
          "email" => $order->get_billing_email(),
          "phone" => $order->get_billing_phone()
        ),
        "total_amount" => $order->get_total(),
        "description" => "Paiement de " . $order->get_total() . " FCFA pour article(s) achetés sur " . get_bloginfo("name")
      ),
      "store" => array(
        "name" => get_bloginfo("name"),
        "website_url" => home_url('/')
      ),
      "actions" => array(
        "cancel_url" => $cancel_url,
        "callback_url" => WC()->api_request_url('WC_Paydunya'),
        "return_url" => $return_url
      ),
      "custom_data" => array(
        "order_id" => $order->get_id(),
        "trans_id" => $order->get_id() . '_' . time()
      )
    );

    return apply_filters('woocommerce_paydunya_args', $paydunya_args, $order);
  }

  // Creates the PayDunya invoice. Returns the PayDunya payment page URL or a WP_Error.
  private function create_invoice($order)
  {
    $response = wp_remote_post($this->posturl, array(
      'headers' => $this->get_api_headers(),
      'body' => wp_json_encode($this->get_paydunya_args($order)),
      'timeout' => 30
    ));

    $error_message = __('Le paiement PAYDUNYA est momentanément indisponible. Veuillez réessayer.', 'paydunya');

    if (is_wp_error($response)) {
      $this->log('Création de facture impossible (commande ' . $order->get_id() . ') : ' . $response->get_error_message());
      return new WP_Error('paydunya_http', $error_message);
    }

    $invoice = json_decode(wp_remote_retrieve_body($response));
    if (!$invoice || !isset($invoice->response_code) || $invoice->response_code != "00" || empty($invoice->token)) {
      $this->log('Facture refusée (commande ' . $order->get_id() . ') : ' . $this->summarize_response($response));
      return new WP_Error('paydunya_api', !empty($invoice->response_text) ? sanitize_text_field($invoice->response_text) : $error_message);
    }

    // The token is stored on the order so its status can be checked with PayDunya later.
    $order->update_meta_data('_paydunya_token', $invoice->token);
    $order->save();
    $order->add_order_note("PAYDUNYA Token: " . $invoice->token);

    return $invoice->response_text;
  }

  // PayDunya API authentication headers (test or production keys).
  private function get_api_headers()
  {
    $sandbox = $this->sandbox == "yes";

    return array(
      'PAYDUNYA-MASTER-KEY' => $this->live_master_key,
      'PAYDUNYA-PRIVATE-KEY' => $sandbox ? $this->test_private_key : $this->live_private_key,
      'PAYDUNYA-TOKEN' => $sandbox ? $this->test_token : $this->live_token,
      'Content-Type' => 'application/json'
    );
  }

  // Asks PayDunya for the actual status of an invoice. Returns the decoded response, or null on failure.
  private function confirm_invoice($invoice_token)
  {
    $response = wp_remote_get($this->geturl . rawurlencode($invoice_token), array(
      'headers' => $this->get_api_headers(),
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      $this->log('Confirmation PayDunya impossible : ' . $response->get_error_message());
      return null;
    }

    $invoice = json_decode(wp_remote_retrieve_body($response));
    if (!$invoice || !isset($invoice->response_code) || $invoice->response_code != "00") {
      $this->log('Réponse PayDunya invalide : ' . $this->summarize_response($response));
      return null;
    }

    return $invoice;
  }

  // Applies the status returned by PayDunya to the WooCommerce order. Used by the customer return
  // (handle_return) and the server notification (callback_handler). Returns the applied status.
  private function update_order_from_invoice($order, $invoice, $invoice_token, $customer_cancelled = false)
  {
  // The invoice must belong to this order.
  // Logged rather than added as an order note: repeated attempts should not
  // clutter the order history.
    if (!isset($invoice->custom_data->order_id) || (int) $invoice->custom_data->order_id !== $order->get_id()) {
      $this->log('La facture ' . $invoice_token . ' ne correspond pas à la commande ' . $order->get_id() . ', statut non modifié.');
      return 'error';
    }

    // Already paid (both the customer return and the server notification may be received).
    if ($order->is_paid()) {
      return 'completed';
    }

    switch ($invoice->status) {
      case 'completed':
        $paid_amount = isset($invoice->invoice->total_amount) ? (float) $invoice->invoice->total_amount : 0;
        if (abs($paid_amount - (float) $order->get_total()) > 0.01) {
          $order->update_status('on-hold', sprintf('PAYDUNYA : montant payé (%s) différent du total de la commande (%s). Vérification manuelle nécessaire.', $paid_amount, $order->get_total()));
          return 'on-hold';
        }

        // payment_complete() marks the order as "Paid" (see paydunya-order-status.php).
        // The merchant manually changes it to "Terminée".
        $order->payment_complete($invoice_token);
        $order->add_order_note('Paiement PAYDUNYA effectué avec succès<br/>ID unique reçu de PAYDUNYA: ' . $invoice_token);

        if ($this->sms == "yes") {
          $customer = trim($order->get_billing_last_name() . " " . $order->get_billing_first_name());
          $sms = $this->sms_message;
          $sms = str_replace("{ORDER-ID}", $order->get_id(), $sms);
          $sms = str_replace("{AMOUNT}", wp_strip_all_tags(wc_price($order->get_total(), array('currency' => $order->get_currency()))), $sms);
          $sms = str_replace("{CUSTOMER}", $customer, $sms);
          $this->sendsms($order->get_billing_phone(), $sms);
        }
        return 'completed';

      case 'cancelled':
        $order->update_status('cancelled', 'PAYDUNYA : paiement annulé.');
        return 'cancelled';

      case 'failed':
        $order->update_status('failed', "PAYDUNYA : la transaction a échoué.");
        return 'failed';

      default:
        // Invoice still "pending" at PayDunya: the order remains "Pending payment",
        // without a note (repeated reloads of the return page would add a new note each time).
        if ($customer_cancelled) {
          $order->update_status('cancelled', 'PAYDUNYA : paiement annulé par le client sur la page de paiement.');
          return 'cancelled';
        }
        return 'pending';
    }
  }

  // Retrieves the token added by PayDunya to the return URL. PayDunya may append it using "?" even if
  // the URL already contains parameters (...&key=wc_order_xxx?token=yyy), hence the search in the raw query string.
  private function get_returned_token()
  {
    if (!empty($_GET['token']) && is_string($_GET['token'])) {
      return sanitize_text_field(wp_unslash($_GET['token']));
    }
    $query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    if (preg_match('/token=([A-Za-z0-9_-]+)/', $query, $matches)) {
      return $matches[1];
    }
    return '';
  }

  // Client return from PayDunya payment page (return_url / cancel_url).
  public function handle_return()
  {
    $order = is_string($_GET['paydunya_return']) ? wc_get_order(absint(wp_unslash($_GET['paydunya_return']))) : false;
    // explode() removes any "?token=..." appended to the key by PayDunya.
    $order_key = isset($_GET['key']) && is_string($_GET['key']) ? explode('?', sanitize_text_field(wp_unslash($_GET['key'])))[0] : '';

    if (!$order || $order->get_payment_method() !== $this->id || !$order->key_is_valid($order_key)) {
      wp_safe_redirect(wc_get_checkout_url());
      exit;
    }

    // Order already confirmed, payment successful (for example, by the server notification): no need to query PayDunya.
    $status = $order->is_paid() ? 'completed' : null;

    if (!$status) {
      // The token stored on the order is used as a fallback if PayDunya has not added it to the URL.
      $invoice_token = $this->get_returned_token();
      if ($invoice_token === '') {
        $invoice_token = $order->get_meta('_paydunya_token');
      }

      $invoice = $invoice_token ? $this->confirm_invoice($invoice_token) : null;
      if (!$invoice) {
        wc_add_notice(__("Impossible de vérifier votre paiement auprès de PAYDUNYA. Si vous avez été débité, contactez-nous avec votre numéro de commande : ", 'paydunya') . $order->get_id(), 'error');
        wp_safe_redirect(wc_get_checkout_url());
        exit;
      }

      $status = $this->update_order_from_invoice($order, $invoice, $invoice_token, isset($_GET['paydunya_cancel']));
    }

    if ($status === 'completed' || $status === 'on-hold') {
      if (WC()->cart) {
        WC()->cart->empty_cart();
      }
      wp_safe_redirect($this->get_return_url($order));
      exit;
    }

    $messages = array(
      'cancelled' => __("Le paiement a été annulé.", 'paydunya'),
      'failed' => __("La transaction n'a pu être complétée.", 'paydunya'),
      'pending' => __("Votre paiement est en attente de confirmation par PAYDUNYA.", 'paydunya')
    );
    wc_add_notice(isset($messages[$status]) ? $messages[$status] : __("Merci de nous avoir choisi. Malheureusement, la transaction a été refusée.", 'paydunya'), $status === 'pending' ? 'notice' : 'error');
    wp_safe_redirect(wc_get_checkout_url());
    exit;
  }

  // Server notification (IPN) sent by PayDunya to the callback_url.
  // Note: PayDunya cannot reach a locally hosted site (localhost); it only works with a site that is online.
  // In a local environment, the status is updated by handle_return() when the customer returns to the site.
  public function callback_handler()
  {
    $data = isset($_POST['data']) && is_array($_POST['data']) ? wp_unslash($_POST['data']) : array();
    // is_string() : une valeur envoyée sous forme de tableau (data[hash][]=...) est rejetée sans avertissement PHP.
    $hash = isset($data['hash']) && is_string($data['hash']) ? $data['hash'] : '';

    if ($hash === '' || !hash_equals(hash('sha512', $this->live_master_key), $hash)) {
      status_header(403);
      exit("Cette requête n'a pas été émise par PayDunya");
    }

    $order_id = isset($data['custom_data']['order_id']) && is_scalar($data['custom_data']['order_id']) ? absint($data['custom_data']['order_id']) : 0;
    $order = wc_get_order($order_id);
    if (!$order || $order->get_payment_method() !== $this->id) {
      status_header(404);
      exit;
    }

    // The status is re-verified with the API check status rather than relying on the received data.
    $invoice_token = $order->get_meta('_paydunya_token');
    if (!$invoice_token && isset($data['invoice']['token']) && is_string($data['invoice']['token'])) {
      $invoice_token = sanitize_text_field($data['invoice']['token']);
    }

    $invoice = $invoice_token ? $this->confirm_invoice($invoice_token) : null;
    if ($invoice) {
      $this->update_order_from_invoice($order, $invoice, $invoice_token);
    }

    status_header(200);
    exit;
  }

  // Sends the confirmation SMS via the API configured by the merchant. The call is non-blocking
  // to avoid slowing down the customer's redirect.
  function sendsms($number, $message)
  {
    if (empty($number) || trim((string) $this->sms_url) === '') {
      return;
    }

    if (strpos($this->sms_url, '{NUMBER}') !== false || strpos($this->sms_url, '{MESSAGE}') !== false) {
      $url = str_replace(array('{NUMBER}', '{MESSAGE}'), array(rawurlencode($number), rawurlencode($message)), $this->sms_url);
    } else {
      
      $url = add_query_arg(array('NUMBER' => rawurlencode($number), 'MESSAGE' => rawurlencode($message)), $this->sms_url);
    }

    $response = wp_remote_get($url, array('timeout' => 5, 'blocking' => false));
    if (is_wp_error($response)) {
      $this->log('Envoi du SMS impossible : ' . $response->get_error_message());
    }
  }

  // Log accessible under WooCommerce > Status > Logs (source "paydunya").
  private function log($message)
  {
    wc_get_logger()->error($message, array('source' => 'paydunya'));
  }

  // Short summary of an HTTP response for logging: HTTP status code and the beginning of the response body without HTML.
  // Prevents entire pages from being logged (PayDunya may return an HTML page in case of an error).
  private function summarize_response($response)
  {
    $body = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags(wp_remote_retrieve_body($response))));
    if (strlen($body) > 300) {
      $body = substr($body, 0, 300) . '…';
    }
    return 'HTTP ' . wp_remote_retrieve_response_code($response) . ' - ' . $body;
  }
}
