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
        add_action('wp_ajax_wscf_update_payment_method', [$this, 'update_payment_method']);
        add_action('wp_ajax_nopriv_wscf_update_payment_method', [$this, 'update_payment_method']);
    }

    /**
     * Log debug messages to WooCommerce debug log
     */
    private function log_debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[WSCF DEBUG] " . $message);
        }
    }

    /**
     * Apply checkout fees and discounts based on payment method & general threshold discount
     */
    public function apply_checkout_fees_and_discounts($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $cart_total = WC()->cart->get_cart_contents_total(); // Get correct cart total
        $chosen_gateway = WC()->session->get('chosen_payment_method', '');

        $this->log_debug("Current Payment Method: " . $chosen_gateway);
        $this->log_debug("Cart Total Before Fees: " . $cart_total);

        // Fetch General Discount Settings (Threshold Discount)
        $discount_threshold = (float) get_option('wscf_discount_threshold', 100);
        $discount_amount = (float) get_option('wscf_discount_amount', 10);

        // Apply general discount if threshold is met
        if ($cart_total >= $discount_threshold && $discount_amount > 0) {
            $this->log_debug("Applying General Discount: -" . $discount_amount);
            $cart->add_fee(__('Bulk Order Discount', 'woo-smart-checkout-fees'), -$discount_amount, false);
        } else {
            $this->log_debug("General Discount Not Applied - Threshold Not Met");
        }

        // Fetch all available payment gateways
        $payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();
        $payment_fees = [];
        $payment_discounts = [];

        foreach ($payment_gateways as $gateway_id => $gateway) {
            // Get fee and discount for the gateway
            $fee_option = (float) get_option("wscf_{$gateway_id}_fee", 0);
            $discount_option = (float) get_option("wscf_{$gateway_id}_discount", 0);

            $this->log_debug("Checking Fee for: $gateway_id | Fee: $fee_option");
            $this->log_debug("Checking Discount for: $gateway_id | Discount: $discount_option");

            $payment_fees[$gateway_id] = $fee_option;
            $payment_discounts[$gateway_id] = $discount_option;
        }

        // Apply gateway-specific processing fee
        if (!empty($chosen_gateway) && isset($payment_fees[$chosen_gateway])) {
            $fee_amount = $payment_fees[$chosen_gateway];

            if ($fee_amount > 0) {
                $this->log_debug("Applying Payment Processing Fee: " . $fee_amount);
                $cart->add_fee(__('Payment Processing Fee', 'woo-smart-checkout-fees'), $fee_amount, false);
            } else {
                $this->log_debug("Removing Payment Fee as selected method has no fee.");
            }
        }

        // Apply gateway-specific discount
        if (!empty($chosen_gateway) && isset($payment_discounts[$chosen_gateway])) {
            $discount_amount = $payment_discounts[$chosen_gateway];

            if ($discount_amount > 0) {
                $this->log_debug("Applying Payment Discount: -" . $discount_amount);
                $cart->add_fee(__('Payment Method Discount', 'woo-smart-checkout-fees'), -$discount_amount, false);
            } else {
                $this->log_debug("No discount for selected method.");
            }
        }

        $this->log_debug("Cart Total After Fees: " . WC()->cart->get_cart_contents_total());
    }

    /**
     * Tracks selected payment method and triggers an AJAX update
     */
    public function track_payment_method() {
        ?>
        <script>
            console.log("✅ WSCF Script Loaded! Checking for Payment Methods...");

            document.addEventListener("DOMContentLoaded", function () {
                console.log("✅ WSCF Script Loaded! Waiting for Payment Method Selection...");

                // Use event delegation to listen for payment method changes
                document.body.addEventListener("change", function (event) {
                    if (event.target.matches('input[name="payment_method"]')) {
                        let selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;

                        console.log("🟢 Payment method changed to:", selectedMethod); // Debugging log

                        // Send AJAX request to update WooCommerce session
                        fetch(wc_checkout_params.ajax_url, {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: new URLSearchParams({
                                action: "wscf_update_payment_method",
                                payment_method: selectedMethod
                            })
                        }).then(response => response.text()).then(data => {
                            console.log("✅ AJAX Response:", data); // Debugging log

                            // Ensure session updates before refreshing checkout
                            setTimeout(() => {
                                console.log("🔄 Triggering WooCommerce Checkout Refresh...");
                                jQuery(document.body).trigger("update_checkout");
                            }, 500);
                        }).catch(error => {
                            console.error("❌ AJAX Error:", error);
                        });
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Updates the WooCommerce session with the selected payment method
     */
    public function update_payment_method() {
        if (isset($_POST['payment_method'])) {
            $method = sanitize_text_field($_POST['payment_method']);

            $this->log_debug("AJAX Received: New Payment Method - " . $method);

            // Update WooCommerce session
            WC()->session->set('chosen_payment_method', $method);
            WC()->cart->calculate_totals();

            echo "Payment method updated to: " . $method;
        } else {
            $this->log_debug("AJAX Failed - No payment method received.");
            echo "Error: No payment method received.";
        }
        wp_die();
    }
}

// Ensure only one instance is initialized
WSCF_Checkout_Fees::get_instance();
