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




// tabs switching code.

document.addEventListener("DOMContentLoaded", function () {
    let tabs = document.querySelectorAll(".nav-tab");
    let contents = document.querySelectorAll(".wscf-tab-content");

    function activateTab(targetId) {
        if (!targetId) return;

        // Remove active class from all tabs
        tabs.forEach(tab => tab.classList.remove("nav-tab-active"));
        // Hide all tab content
        contents.forEach(content => content.style.display = "none");

        // Activate clicked tab
        let targetTab = document.querySelector(`.nav-tab[href="${targetId}"]`);
        if (targetTab) {
            targetTab.classList.add("nav-tab-active");
        }

        // Show corresponding tab content
        let targetContent = document.querySelector(targetId);
        if (targetContent) {
            targetContent.style.display = "block";
        }

        // Update URL hash without reloading the page
        history.replaceState(null, null, targetId);
    }

    // Click event for switching tabs
    tabs.forEach(tab => {
        tab.addEventListener("click", function (e) {
            e.preventDefault();
            activateTab(this.getAttribute("href"));
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