<?php
/**
 * Plugin Name: WooCommerce Smart Checkout Fees & Discounts
 * Plugin URI: https://yourwebsite.com/
 * Description: Automatically applies checkout fees and discounts based on cart conditions, payment methods, and order total.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com/
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
    new WSCF_Checkout_Fees(); // Load checkout fees logic
    new WSCF_Admin_Settings(); // Load admin settings panel
}

add_action('plugins_loaded', 'wscf_init_plugin');
