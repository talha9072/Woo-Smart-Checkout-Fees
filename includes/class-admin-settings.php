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
        woocommerce_admin_fields($this->get_settings());
    }

    public function save_settings() {
        woocommerce_update_options($this->get_settings());
    }

    private $settings = null;

    public function get_settings() {
        if ($this->settings === null) {
            $this->settings = [
                [
                    'title' => __('Checkout Fees & Discounts Settings', 'woo-smart-checkout-fees'),
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
                    'title'    => __('PayPal Processing Fee', 'woo-smart-checkout-fees'),
                    'desc'     => __('Extra fee when PayPal is selected.', 'woo-smart-checkout-fees'),
                    'id'       => 'wscf_paypal_fee',
                    'type'     => 'number',
                    'default'  => 3,
                    'desc_tip' => true
                ],
                [
                    'title'    => __('Stripe Processing Fee', 'woo-smart-checkout-fees'),
                    'desc'     => __('Extra fee when Stripe is selected.', 'woo-smart-checkout-fees'),
                    'id'       => 'wscf_stripe_fee',
                    'type'     => 'number',
                    'default'  => 2,
                    'desc_tip' => true
                ],
                [
                    'type' => 'sectionend',
                    'id'   => 'wscf_settings_section'
                ],
            ];
        }
        return $this->settings;
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'woocommerce_page_wscf_checkout_fees') {
            return;
        }

        wp_enqueue_style('wscf-admin-style', WSCF_PLUGIN_URL . 'assets/styles.css', [], '1.0');
        wp_enqueue_script('wscf-admin-script', WSCF_PLUGIN_URL . 'assets/script.js', ['jquery'], '1.0', true);
    }
}

// Ensure only one instance of the class is initialized
WSCF_Admin_Settings::get_instance();

}
