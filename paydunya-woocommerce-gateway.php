<?php

/*
Plugin Name: Passerelle de paiement PAYDUNYA pour WooCommerce
Plugin URI: https://paydunya.com/developers/wordpress
Description: Intégrer facilement des paiements via Orange Money dans votre site WooCommerce et commencer à accepter les paiements depuis le Sénégal.
Version: 1.1.5
Author: PAYDUNYA
Author URI: https://paydunya.com
*/

if (!defined('ABSPATH')) {
	exit;
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	exit;
}

add_action('plugins_loaded', 'woocommerce_paydunya_init', 0);

function woocommerce_paydunya_init() {
	if (!class_exists('WC_Payment_Gateway'))
			return;

	class WC_Paydunya extends WC_Payment_Gateway {

			public function __construct() {
					$this->paydunya_errors = new WP_Error();

					$this->id = 'paydunya';
					$this->medthod_title = 'PAYDUNYA';
					$this->icon = apply_filters('woocommerce_paydunya_icon', plugins_url('assets/images/logo.png', __FILE__));
					$this->has_fields = false;

					$this->init_form_fields();
					$this->init_settings();

					$this->title = $this->settings['title'];
					$this->description = $this->settings['description'];

					$this->live_master_key = $this->settings['master_key'];

					$this->live_private_key = $this->settings['live_private_key'];
					$this->live_token = $this->settings['live_token'];

					$this->test_private_key = $this->settings['test_private_key'];
					$this->test_token = $this->settings['test_token'];

					$this->sandbox = $this->settings['sandbox'];

					$this->sms = $this->settings['sms'];
					$this->sms_url = $this->settings['sms_url'];
					$this->sms_message = $this->settings['sms_message'];

					if ($this->settings['sandbox'] == "yes") {
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
			}

			function sendsms($number, $message) {
					$url = $this->sms_url;
					$url = str_replace("{NUMBER}", urlencode($number), $url);
					$url = str_replace("{MESSAGE}", urlencode($message), $url);
					$url = str_replace("amp;", "&", $url);
					if (trim($url) <> "") {
							$curl = curl_init();
							curl_setopt_array($curl, array(
									CURLOPT_RETURNTRANSFER => 1,
									CURLOPT_URL => $url
							));
							curl_exec($curl);
							curl_close($curl);
					}
			}

			function init_form_fields() {
					$this->form_fields = array(
							'enabled' => array(
									'title' => __('Activer/Désactiver', 'paydunya'),
									'type' => 'checkbox',
									'label' => __('Activer le module de paiement PAYDUNYA.', 'paydunya'),
									'default' => 'no'),
							'title' => array(
									'title' => __('Titre:', 'paydunya'),
									'type' => 'text',
									'description' => __('Texte que verra le client lors du paiement de sa commande.', 'paydunya'),
									'default' => __('Paiement avec PAYDUNYA', 'paydunya')),
							'description' => array(
									'title' => __('Description:', 'paydunya'),
									'type' => 'textarea',
									'description' => __('Description que verra le client lors du paiement de sa commande.', 'paydunya'),
									'default' => __('PAYDUNYA est la passerelle de paiement la plus populaire pour les achats en ligne au Sénégal.', 'paydunya')),
							'master_key' => array(
									'title' => __('Clé Principale', 'paydunya'),
									'type' => 'text',
									'description' => __('Clé principale fournie par PAYDUNYA lors de la création de votre application.')),
							'live_private_key' => array(
									'title' => __('Clé Privée de production', 'paydunya'),
									'type' => 'text',
									'description' => __('Clé Privée de production fournie par PAYDUNYA lors de la création de votre application.')),
							'live_token' => array(
									'title' => __('Token de production', 'paydunya'),
									'type' => 'text',
									'description' => __('Token de production fourni par PAYDUNYA lors de la création de votre application.')),
							'test_private_key' => array(
									'title' => __('Clé Privée de test', 'paydunya'),
									'type' => 'text',
									'description' => __('Clé Privée de test fournie par PAYDUNYA lors de la création de votre application.')),
							'test_token' => array(
									'title' => __('Token de test', 'paydunya'),
									'type' => 'text',
									'description' => __('Token de test fourni par PAYDUNYA lors de la création de votre application.')),
							'sandbox' => array(
									'title' => __('Activer le mode test', 'paydunya'),
									'type' => 'checkbox',
									'description' => __("Cocher cette case si vous êtes encore à l'etape des paiements tests.", 'paydunya')),
							'sms' => array(
									'title' => __('Notification SMS', 'paydunya'),
									'type' => 'checkbox',
									'default' => 'no',
									'description' => __("Activer l'envoi de notification par SMS en cas de succès de paiement sur PAYDUNYA.", 'paydunya')),
							'sms_url' => array(
									'title' => __("URL de votre API REST d'envoi de SMS"),
									'type' => 'text',
									'description' => __('Utilisez {NUMBER} pour indiquer le numéro du client et {MESSAGE} pour le message.')),
							'sms_message' => array(
									'title' => __('Contenu du SMS envoyé en cas de succès de paiement'),
									'type' => 'textarea',
									'description' => __("Utilisez {ORDER-ID} pour indiquer l'identifiant de commande, {AMOUNT} pour le montant et {CUSTOMER} pour le nom du client."))
					);
			}

			public function admin_options() {
					echo '<h3>' . __('Passerelle de paiement PAYDUNYA', 'paydunya') . '</h3>';
					echo '<p>' . __('PAYDUNYA est la passerelle de paiement la plus populaire pour les achats en ligne au Sénégal.') . '</p>';
					echo '<table class="form-table">';
					// Generate the HTML For the settings form.
					$this->generate_settings_html();
					echo '</table>';
					wp_enqueue_script('paydunya_admin_option_js', plugin_dir_url(__FILE__) . 'assets/js/settings.js', array('jquery'), '1.0.1');
			}

			function payment_fields() {
					if ($this->description)
							echo wpautop(wptexturize($this->description));
			}

			protected function get_paydunya_args($order) {
					global $woocommerce;

					//$order = new WC_Order($order_id);
					$txnid = $order->id . '_' . date("ymds");

					$redirect_url = $woocommerce->cart->get_checkout_url();

					$productinfo = "Commande: " . $order->id;

					$str = "$this->merchant_id|$txnid|$order->order_total|$productinfo|$order->billing_first_name|$order->billing_email|||||||||||$this->salt";
					$hash = hash('sha512', $str);

					WC()->session->set('paydunya_wc_hash_key', $hash);

					$items = $woocommerce->cart->get_cart();
					$paydunya_items = array();
					foreach ($items as $item) {
							$paydunya_items[] = array(
									"name" => $item["data"]->post->post_title,
									"quantity" => $item["quantity"],
									"unit_price" => $item["line_total"] / (($item["quantity"] == 0) ? 1 : $item["quantity"]),
									"total_price" => $item["line_total"],
									"description" => ""
							);
					}
					$paydunya_args = array(
							"invoice" => array(
									"items" => $paydunya_items,
									"total_amount" => $order->order_total,
									"description" => "Paiement de " . $order->order_total . " FCFA pour article(s) achetés sur " . get_bloginfo("name")
							), "store" => array(
									"name" => get_bloginfo("name"),
									//"logo_url" => "",
									"website_url" => get_site_url()
							), "actions" => array(
									"cancel_url" => $redirect_url,
									"return_url" => $redirect_url
							), "custom_data" => array(
									"order_id" => $order->id,
									"trans_id" => $txnid,
									"hash" => $hash
							)
					);


					apply_filters('woocommerce_paydunya_args', $paydunya_args, $order);
					return $paydunya_args;
			}

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
		curl_setopt($ch, CURLOPT_NOBODY, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
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
							$order = new WC_Order($order_id);
							$order->add_order_note("PAYDUNYA Token: " . $response_decoded->token);
							return $response_decoded->response_text;
					} else {
							global $woocommerce;
							$url = $woocommerce->cart->get_checkout_url();
							if (strstr($url, "?")) {
									return $url . "&paydunya=" . $response_decoded->response_text;
							} else {
									return $url . "?paydunya=" . $response_decoded->response_text;
							}
					}	
		
			}
	
	
			function process_payment($order_id) {
					$order = new WC_Order($order_id);
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

			function check_paydunya_response($mtoken) {
					global $woocommerce;
					if ($mtoken <> "") {
							$wc_order_id = WC()->session->get('paydunya_wc_oder_id');
							$hash = WC()->session->get('paydunya_wc_hash_key');
							$order = new WC_Order($wc_order_id);
							try {
									$ch = curl_init();
									$master_key = $this->live_master_key;
									$private_key = "";
									$url = $this->geturl . $mtoken;
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
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
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
													$total_amount = strip_tags($woocommerce->cart->get_cart_total());
													$message = "Merci pour votre achat. La transaction a été un succès, le paiement a été reçu. Votre commande est en cours de traitement. Votre numéro de commande est $order_id";
													$message_type = "success";
													$order->payment_complete();
													$order->update_status('completed');
													$order->add_order_note('Paiement PAYDUNYA effectué avec succès<br/>ID unique reçu de PAYDUNYA: ' . $mtoken);
													$order->add_order_note($this->msg['message']);
													$woocommerce->cart->empty_cart();
													$redirect_url = $this->get_return_url($order);
													$customer = trim($order->billing_last_name . " " . $order->billing_first_name);
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
											'message' => $message,
											'message_type' => $message_type
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
									$order->add_order_note('Erreur: ' . $e->getMessage());

									$redirect_url = $order->get_cancel_order_url();
									wp_redirect($redirect_url);
									exit;
							}
					}
			}

			static function add_paydunya_fcfa_currency($currencies) {
					$currencies['FCFA'] = __('BCEAO XOF', 'woocommerce');
					return $currencies;
			}

			static function add_paydunya_fcfa_currency_symbol($currency_symbol, $currency) {
					switch (
					$currency) {
							case 'FCFA': $currency_symbol = 'FCFA';
									break;
					}
					return $currency_symbol;
			}

			static function woocommerce_add_paydunya_gateway($methods) {
					$methods[] = 'WC_Paydunya';
					return $methods;
			}

			// Add settings link on plugin page
			static function woocommerce_add_paydunya_settings_link($links) {
					$settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_paydunya">Paramètres</a>';
					array_unshift($links, $settings_link);
					return $links;
			}

	}

	$plugin = plugin_basename(__FILE__);

	add_filter('woocommerce_currencies', array('WC_Paydunya', 'add_paydunya_fcfa_currency'));
	add_filter('woocommerce_currency_symbol', array('WC_Paydunya', 'add_paydunya_fcfa_currency_symbol'), 10, 2);

	add_filter("plugin_action_links_$plugin", array('WC_Paydunya', 'woocommerce_add_paydunya_settings_link'));
	add_filter('woocommerce_payment_gateways', array('WC_Paydunya', 'woocommerce_add_paydunya_gateway'));
}