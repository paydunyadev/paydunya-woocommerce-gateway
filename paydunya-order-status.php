<?php

/**
 * "Payée" order status (wc-paydunya-paid).
 *
 * After a successful PayDunya payment, all orders (physical or virtual products) transition to "Payée"
 * instead of "En cours" or "Terminée". The merchant can then mark them as "Terminée".
 */

if (!defined('ABSPATH')) {
    exit;
}

// Slug without the "wc-" prefix (limited to 20 characters including the prefix).
define('PAYDUNYA_PAID_STATUS', 'paydunya-paid');

// Registers the status with WordPress (orders stored as posts / compatibility mode).
add_action('init', 'paydunya_register_paid_status');
function paydunya_register_paid_status()
{
    register_post_status('wc-' . PAYDUNYA_PAID_STATUS, array(
        'label' => _x('Payée', 'Statut de commande', 'paydunya'),
        'public' => false,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        /* translators: %s: nombre de commandes */
        'label_count' => _n_noop('Payée <span class="count">(%s)</span>', 'Payées <span class="count">(%s)</span>', 'paydunya'),
    ));
}

// Adds "Paid" to the list of WooCommerce statuses, right after "Processing".
add_filter('wc_order_statuses', 'paydunya_add_paid_status');
function paydunya_add_paid_status($order_statuses)
{
    $new_statuses = array();
    foreach ($order_statuses as $key => $label) {
        $new_statuses[$key] = $label;
        if ('wc-processing' === $key) {
            $new_statuses['wc-' . PAYDUNYA_PAID_STATUS] = _x('Payée', 'Statut de commande', 'paydunya');
        }
    }
    if (!isset($new_statuses['wc-' . PAYDUNYA_PAID_STATUS])) {
        $new_statuses['wc-' . PAYDUNYA_PAID_STATUS] = _x('Payée', 'Statut de commande', 'paydunya');
    }
    return $new_statuses;
}

// Status applied by payment_complete() for PayDunya orders: "Paid" instead of
// "Processing" (physical products) or "Completed" (virtual/downloadable products).
add_filter('woocommerce_payment_complete_order_status', 'paydunya_payment_complete_status', 10, 3);
function paydunya_payment_complete_status($status, $order_id, $order = null)
{
    if (!$order) {
        $order = wc_get_order($order_id);
    }
    if ($order && 'paydunya' === $order->get_payment_method() && in_array($status, array('processing', 'completed'), true)) {
        return PAYDUNYA_PAID_STATUS;
    }
    return $status;
}

// "Payée" is treated as a paid order (is_paid(), reports, dashboard).
add_filter('woocommerce_order_is_paid_statuses', 'paydunya_add_to_paid_statuses');
add_filter('woocommerce_reports_order_statuses', 'paydunya_add_to_paid_statuses');
function paydunya_add_to_paid_statuses($statuses)
{
    $statuses[] = PAYDUNYA_PAID_STATUS;
    return $statuses;
}

// Downloadable products: same access permissions as a "Processing" order (WooCommerce setting
// "Grant access to downloadable products after payment").
add_action('woocommerce_order_status_' . PAYDUNYA_PAID_STATUS, 'wc_downloadable_product_permissions');
add_filter('woocommerce_order_is_download_permitted', 'paydunya_paid_download_permitted', 10, 2);
function paydunya_paid_download_permitted($permitted, $order)
{
    if (!$permitted && $order->has_status(PAYDUNYA_PAID_STATUS)) {
        return 'yes' === get_option('woocommerce_downloads_grant_access_after_payment');
    }
    return $permitted;
}

// Emails: WooCommerce only sends "New Order" (merchant) and "Processing Order" (customer)
// for its own statuses. We trigger them when the order transitions to "Paid".
add_action('woocommerce_order_status_changed', 'paydunya_paid_status_emails', 10, 4);
function paydunya_paid_status_emails($order_id, $from, $to, $order)
{
    if (PAYDUNYA_PAID_STATUS !== $to || !in_array($from, array('pending', 'failed', 'cancelled', 'on-hold'), true)) {
        return;
    }

    $emails = WC()->mailer()->get_emails();
    foreach (array('WC_Email_New_Order', 'WC_Email_Customer_Processing_Order') as $email_class) {
        if (isset($emails[$email_class])) {
            $emails[$email_class]->trigger($order_id, $order);
        }
    }
}

// "Complete" button in the order list for "Paid" orders.
add_filter('woocommerce_admin_order_actions', 'paydunya_paid_admin_order_actions', 10, 2);
function paydunya_paid_admin_order_actions($actions, $order)
{
    if ($order->has_status(PAYDUNYA_PAID_STATUS) && !isset($actions['complete'])) {
        $actions['complete'] = array(
            'url' => wp_nonce_url(admin_url('admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $order->get_id()), 'woocommerce-mark-order-status'),
            'name' => __('Complete', 'woocommerce'),
            'action' => 'complete',
        );
    }
    return $actions;
}

// Color of the "Payée" badge in the order list (green, like "Processing").
add_action('admin_head', 'paydunya_paid_status_style');
function paydunya_paid_status_style()
{
    echo '<style>.order-status.status-' . PAYDUNYA_PAID_STATUS . '{background:#c6e1c6;color:#2c4700;}</style>';
}
