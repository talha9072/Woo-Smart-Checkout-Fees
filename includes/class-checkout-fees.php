<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WSCF_Checkout_Fees {

    public function __construct() {
        // Hook into WooCommerce to apply fees
        add_action('woocommerce_cart_calculate_fees', [$this, 'apply_checkout_fees_and_discounts']);
        
        // Hook into payment selection to track chosen payment method
        add_action('woocommerce_review_order_before_payment', [$this, 'track_payment_method']);
    }

    /**
     * Apply checkout fees and discounts dynamically
     */
    public function apply_checkout_fees_and_discounts($cart) {
        // Prevent execution in admin area
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        // Get cart total
        $cart_total = $cart->subtotal;
        
        // Get chosen payment method
        $chosen_gateway = WC()->session->get('chosen_payment_method');

        // Get settings from admin panel (we will create this in class-admin-settings.php)
        $discount_threshold = get_option('wscf_discount_threshold', 100); // Default: $100
        $discount_amount = get_option('wscf_discount_amount', 10); // Default: $10 discount
        $paypal_fee = get_option('wscf_paypal_fee', 3); // Default: $3 fee for PayPal
        $stripe_fee = get_option('wscf_stripe_fee', 2); // Default: $2 fee for Stripe

        // 1️⃣ Apply Discount for Large Orders
        if ($cart_total >= $discount_threshold) {
            $cart->add_fee(__('Bulk Order Discount', 'woo-smart-checkout-fees'), -$discount_amount, false);
        }

        // 2️⃣ Apply Extra Fee for Specific Payment Methods
        $payment_fees = array(
            'paypal' => $paypal_fee,
            'stripe' => $stripe_fee
        );

        if (isset($payment_fees[$chosen_gateway]) && $payment_fees[$chosen_gateway] > 0) {
            $cart->add_fee(__('Payment Processing Fee', 'woo-smart-checkout-fees'), $payment_fees[$chosen_gateway], false);
        }
    }

    /**
     * Track payment method selection
     */
    public function track_payment_method() {
        ?>
        <script>
            jQuery(document).ready(function($) {
                $('form.checkout').on('change', 'input[name="payment_method"]', function() {
                    var paymentMethod = $('input[name="payment_method"]:checked').val();
                    jQuery.ajax({
                        type: "POST",
                        url: wc_checkout_params.ajax_url,
                        data: {
                            action: 'wscf_update_payment_method',
                            payment_method: paymentMethod
                        }
                    });
                });
            });
        </script>
        <?php
    }
}

// Initialize the checkout fee logic
new WSCF_Checkout_Fees();
