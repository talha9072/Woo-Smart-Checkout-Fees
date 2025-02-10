<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate class declaration
if (!class_exists('WSCF_Admin_Settings')) {

class WSCF_Admin_Settings {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('woocommerce_settings_tabs_array', [$this, 'add_settings_tab'], 50);
        add_action('woocommerce_settings_tabs_wscf_checkout_fees', [$this, 'output_settings']);
        add_action('woocommerce_update_options_wscf_checkout_fees', [$this, 'save_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_settings_tab($settings_tabs) {
        if (!isset($settings_tabs['wscf_checkout_fees'])) {
            $settings_tabs['wscf_checkout_fees'] = __('Checkout Fees & Discounts', 'woo-smart-checkout-fees');
        }
        return $settings_tabs;
    }

    public function output_settings() {
        ?>
        <div class="wscf-tabs-container">
            <h2><?php _e('Checkout Fees & Discounts Settings', 'woo-smart-checkout-fees'); ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="#wscf-tab-general" class="nav-tab nav-tab-active"><?php _e('General Settings', 'woo-smart-checkout-fees'); ?></a>
                <a href="#wscf-tab-payment-methods" class="nav-tab"><?php _e('Payment Method Fees & Discounts', 'woo-smart-checkout-fees'); ?></a>
            </h2>
    
            <div id="wscf-tab-general" class="wscf-tab-content">
                <?php woocommerce_admin_fields($this->get_general_settings()); ?>
            </div>
    
            <div id="wscf-tab-payment-methods" class="wscf-tab-content" style="display: none;">
                <?php woocommerce_admin_fields($this->get_payment_settings()); ?>
            </div>
        </div>
    
        <style>
            .wscf-tab-content { display: none; } /* Hide all tab contents initially */
            .wscf-tab-content:first-of-type { display: block; } /* Show the first tab by default */
        </style>
    
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            let customTabs = document.querySelectorAll(".wscf-tabs-container .nav-tab"); // Only target custom tabs
            let wooCommerceTabs = document.querySelectorAll(".subsubsub a, .nav-tab-wrapper .nav-tab"); // WooCommerce settings tabs
            let contents = document.querySelectorAll(".wscf-tab-content");
    
            function activateTab(targetId) {
                if (!targetId.startsWith("#")) return; // Ensure it's a valid selector
    
                // Remove active class from all custom tabs
                customTabs.forEach(tab => tab.classList.remove("nav-tab-active"));
                // Hide all custom tab content
                contents.forEach(content => content.style.display = "none");
    
                // Activate clicked tab
                let targetTab = document.querySelector(`.wscf-tabs-container .nav-tab[href="${targetId}"]`);
                if (targetTab) {
                    targetTab.classList.add("nav-tab-active");
                }
    
                // Show corresponding tab content
                let targetContent = document.querySelector(targetId);
                if (targetContent) {
                    targetContent.style.display = "block";
                }
    
                // Update URL hash without reloading the page
                let url = new URL(window.location.href);
                url.hash = targetId; // Set only the hash
                history.replaceState(null, null, url.toString());
            }
    
            // Click event for switching custom tabs inside "Checkout Fees & Discounts"
            customTabs.forEach(tab => {
                tab.addEventListener("click", function (e) {
                    e.preventDefault();
                    activateTab(this.getAttribute("href"));
                });
            });
    
            // Ensure WooCommerce settings tabs (General, Payments, Emails, etc.) reload the page
            wooCommerceTabs.forEach(tabLink => {
                tabLink.addEventListener("click", function (e) {
                    if (!this.href.includes("#wscf-tab")) {
                        // Only reload if it's not a custom tab
                        window.location.href = this.href;
                    }
                });
            });
    
            // Auto-activate tab from URL hash on page load
            if (window.location.hash && document.querySelector(window.location.hash)) {
                activateTab(window.location.hash);
            } else {
                // Default to the first tab if no hash is present
                activateTab("#wscf-tab-general");
            }
        });
        </script>
    
        <?php
    }
    
    

    public function save_settings() {
        woocommerce_update_options($this->get_general_settings());
        woocommerce_update_options($this->get_payment_settings());
    }

    private function get_general_settings() {
        return [
            [
                'title' => __('General Settings', 'woo-smart-checkout-fees'),
                'type'  => 'title',
                'desc'  => __('Configure automatic checkout fees and discounts.', 'woo-smart-checkout-fees'),
                'id'    => 'wscf_settings_section'
            ],
            [
                'title'    => __('Discount Threshold', 'woo-smart-checkout-fees'),
                'desc'     => __('Minimum cart total to apply discount.', 'woo-smart-checkout-fees'),
                'id'       => 'wscf_discount_threshold',
                'type'     => 'number',
                'default'  => 100,
                'desc_tip' => true
            ],
            [
                'title'    => __('Discount Amount', 'woo-smart-checkout-fees'),
                'desc'     => __('Amount to discount when threshold is met.', 'woo-smart-checkout-fees'),
                'id'       => 'wscf_discount_amount',
                'type'     => 'number',
                'default'  => 10,
                'desc_tip' => true
            ],
            [
                'type' => 'sectionend',
                'id'   => 'wscf_settings_section'
            ]
        ];
    }

    private function get_payment_settings() {
        $settings = [];
        $payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();

        if (!empty($payment_gateways)) {
            $settings[] = [
                'title' => __('Payment Method Fees & Discounts', 'woo-smart-checkout-fees'),
                'type'  => 'title',
                'desc'  => __('Set fees and discounts for each available payment method.', 'woo-smart-checkout-fees'),
                'id'    => 'wscf_payment_methods_section'
            ];

            foreach ($payment_gateways as $gateway_id => $gateway) {
                $settings[] = [
                    'title' => sprintf(__('Fee for %s', 'woo-smart-checkout-fees'), $gateway->get_title()),
                    'desc'  => __('Extra fee when this payment method is selected.', 'woo-smart-checkout-fees'),
                    'id'    => "wscf_{$gateway_id}_fee",
                    'type'  => 'number',
                    'default' => 0,
                    'desc_tip' => true
                ];

                $settings[] = [
                    'title' => sprintf(__('Discount for %s', 'woo-smart-checkout-fees'), $gateway->get_title()),
                    'desc'  => __('Discount when this payment method is selected.', 'woo-smart-checkout-fees'),
                    'id'    => "wscf_{$gateway_id}_discount",
                    'type'  => 'number',
                    'default' => 0,
                    'desc_tip' => true
                ];
            }

            $settings[] = [
                'type' => 'sectionend',
                'id'   => 'wscf_payment_methods_section'
            ];
        }

        return $settings;
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'woocommerce_page_wscf_checkout_fees') {
            return;
        }

        wp_enqueue_style('wscf-admin-style', WSCF_PLUGIN_URL . 'assets/styles.css', [], '1.0');
    }
}
}

// Ensure only one instance of the class is initialized
WSCF_Admin_Settings::get_instance();
