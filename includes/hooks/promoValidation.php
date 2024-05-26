<?php

use WHMCS\Database\Capsule;

define('TRIAL_PRODUCT_ID', '<YOUR_PRODUCT_ID>'); // Insert Actual Product ID here. 
define('kt_promptRemoval', 'modal'); // Choose one of the following options: "bootstrap-alert", "modal", "js-alert"
define('kt_textDisallowed', 'You must apply a promo code to checkout with a trial product.'); // Error message for missing promo code
define('kt_textRequireProduct', 'You cannot use a promo code for this trial product again.'); // Error message for reusing promo code

// Hook to check if the user has an existing trial product and enforce promo code application
add_hook('ShoppingCartValidateCheckout', 1, function($vars) {
    $userId = $vars['clientId'] ?? null;
    $promoCodeApplied = !empty($vars['promocode']);
    $errorMessage = '';
    $email = $vars['email'] ?? '';

    // Check if the trial product is in the cart
    $cartProducts = isset($_SESSION['cart']['products']) ? $_SESSION['cart']['products'] : [];

    foreach ($cartProducts as $product) {
        if ($product['pid'] == TRIAL_PRODUCT_ID) {
            // Check if the user has already used the trial product
            $usedTrialProduct = false;
            if ($userId) {
                $usedTrialProduct = Capsule::table('tblhosting')
                    ->where('userid', $userId)
                    ->where('packageid', TRIAL_PRODUCT_ID)
                    ->whereIn('domainstatus', ['Pending', 'Active', 'Suspended', 'Terminated', 'Cancelled', 'Fraud', 'Completed'])
                    ->exists();
            } elseif ($email) {
                $usedTrialProduct = Capsule::table('tblhosting')
                    ->where('domainstatus', '!=', 'Cancelled')
                    ->where('packageid', TRIAL_PRODUCT_ID)
                    ->where('email', $email)
                    ->exists();
            }

            if ($usedTrialProduct) {
                return kt_textRequireProduct;
            }

            // Enforce promo code application
            if (!$promoCodeApplied) {
                return kt_textDisallowed;
            }
        }
    }

    return ''; // No error message, proceed with checkout
});

// Admin Area Customization: Highlight and label the trial product
add_hook('AdminAreaHeadOutput', 1, function($vars) {
    if ($vars['filename'] == 'configproducts') {
        $objPrododucts = json_encode([TRIAL_PRODUCT_ID]);

        return <<<HTML
<script type="text/javascript">
$(document).ready(function() {
    $.each({$objPrododucts}, function(key, value) {
        $('#tableBackground > table > tbody  > tr').find("a[href$='?action=edit&id=" + value + "']").closest('tr').find('td').css('background-color', '#d2eed0');
        $('#tableBackground > table > tbody  > tr').find("a[href$='?action=edit&id=" + value + "']").closest('tr').find('td:first').append(' <label class="label label-success">Promo</label>');
    });
});
</script>
HTML;
    }
});
