<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WSCF_Checkout_Fees {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('woocommerce_cart_calculate_fees', [$this, 'apply_checkout_fees_and_discounts']);
        add_action('woocommerce_review_order_before_payment', [$this, 'track_payment_method']);
    }

    public function apply_checkout_fees_and_discounts($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $cart_total = $cart->subtotal;
        $chosen_gateway = WC()->session->get('chosen_payment_method');

        $discount_threshold = get_option('wscf_discount_threshold', 100);
        $discount_amount = get_option('wscf_discount_amount', 10);
        $paypal_fee = get_option('wscf_paypal_fee', 3);
        $stripe_fee = get_option('wscf_stripe_fee', 2);

        if ($cart_total >= $discount_threshold) {
            $cart->add_fee(__('Bulk Order Discount', 'woo-smart-checkout-fees'), -$discount_amount, false);
        }

        $payment_fees = [
            'paypal' => $paypal_fee,
            'stripe' => $stripe_fee
        ];

        if (isset($payment_fees[$chosen_gateway]) && $payment_fees[$chosen_gateway] > 0) {
            $cart->add_fee(__('Payment Processing Fee', 'woo-smart-checkout-fees'), $payment_fees[$chosen_gateway], false);
        }
    }
}

// Ensure only one instance is initialized
WSCF_Checkout_Fees::get_instance();
