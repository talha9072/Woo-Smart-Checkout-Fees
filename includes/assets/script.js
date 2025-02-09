// WooCommerce Smart Checkout Fees & Discounts - Admin JavaScript
jQuery(document).ready(function ($) {
    // Highlight changes when an input field is updated
    $('.wscf-settings-wrap input').on('change', function () {
        $(this).css('border', '2px solid #007cba');
    });

    // Show alert if PayPal fee is very high
    $('#wscf_paypal_fee').on('change', function () {
        if ($(this).val() > 10) {
            alert("Warning: High PayPal processing fee detected!");
        }
    });
});
