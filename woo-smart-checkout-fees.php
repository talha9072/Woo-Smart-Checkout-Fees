<?php
/**
 * Plugin Name: WooCommerce Smart Checkout Fees & Discounts
 * Plugin URI: https://talhasolutions.com/
 * Description: Automatically applies checkout fees and discounts based on cart conditions, payment methods, and order total.
 * Version: 1.0.0
 * Author: Talha Shahid
 * Author URI: https://talha-solutions.com/
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-smart-checkout-fees
 * Domain Path: /languages
 *
 * @package WooSmartCheckoutFees
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Define plugin path
define('WSCF_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WSCF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once WSCF_PLUGIN_PATH . 'includes/class-checkout-fees.php';
require_once WSCF_PLUGIN_PATH . 'includes/class-admin-settings.php';

// Initialize the plugin
function wscf_init_plugin() {
    WSCF_Checkout_Fees::get_instance(); // Load checkout fees logic
    WSCF_Admin_Settings::get_instance(); // Load admin settings panel
}

add_action('plugins_loaded', 'wscf_init_plugin');
