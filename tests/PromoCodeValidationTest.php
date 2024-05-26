<?php

/**
 * Promo Code Validation Tests
 * 
 * Author: Carlos Maundu
 */

use PHPUnit\Framework\TestCase;
use Mockery as m;
require 'vendor/autoload.php';

class PromoCodeValidationTest extends TestCase
{
    protected function setUp(): void
    {
        // Mock the Capsule class and its methods
        $this->capsule = m::mock('alias:WHMCS\Database\Capsule');
        
        // Mock session data
        $_SESSION = [
            'cart' => [
                'products' => [['pid' => PROMO_TRIAL_PRODUCT_ID]],
                'promo' => 'PROMO2023'
            ]
        ];
    }

    protected function tearDown(): void
    {
        // Close mockery
        m::close();
    }

    public function testUserWithExistingTrialProduct()
    {
        $_POST['promocode'] = 'PROMO2023';
        $_POST['email'] = 'test@example.com';

        // Set up the expectation for the Capsule mock
        $this->capsule->shouldReceive('table->where->where->whereIn->exists')
            ->andReturn(true);

        $result = add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
            $trialProductId = PROMO_TRIAL_PRODUCT_ID;
            $promoCodeApplied = !empty($vars['promocode']);
            $userId = 1; // Simulating a logged-in user with ID 1

            foreach ($_SESSION['cart']['products'] as $product) {
                if ($product['pid'] == $trialProductId) {
                    $hasExistingTrialProduct = Capsule::table('tblhosting')
                        ->where('userid', $userId)
                        ->where('packageid', $trialProductId)
                        ->whereIn('domainstatus', ['Pending', 'Active', 'Suspended', 'Terminated', 'Cancelled', 'Fraud', 'Completed'])
                        ->exists();

                    if ($hasExistingTrialProduct) {
                        return PROMO_TEXT_REQUIRE_PRODUCT;
                    }

                    if (!$promoCodeApplied) {
                        return PROMO_TEXT_DISALLOWED;
                    }
                }
            }

            return '';
        });

        $this->assertEquals(PROMO_TEXT_REQUIRE_PRODUCT, $result);
    }

    public function testUserWithoutExistingTrialProduct()
    {
        $_POST['promocode'] = 'PROMO2023';
        $_POST['email'] = 'newuser@example.com';

        // Set up the expectation for the Capsule mock
        $this->capsule->shouldReceive('table->where->where->whereIn->exists')
            ->andReturn(false);

        $result = add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
            $trialProductId = PROMO_TRIAL_PRODUCT_ID;
            $promoCodeApplied = !empty($vars['promocode']);
            $userId = 3; // Simulating a new user with ID 3

            foreach ($_SESSION['cart']['products'] as $product) {
                if ($product['pid'] == $trialProductId) {
                    $hasExistingTrialProduct = Capsule::table('tblhosting')
                        ->where('userid', $userId)
                        ->where('packageid', $trialProductId)
                        ->whereIn('domainstatus', ['Pending', 'Active', 'Suspended', 'Terminated', 'Cancelled', 'Fraud', 'Completed'])
                        ->exists();

                    if ($hasExistingTrialProduct) {
                        return PROMO_TEXT_REQUIRE_PRODUCT;
                    }

                    if (!$promoCodeApplied) {
                        return PROMO_TEXT_DISALLOWED;
                    }
                }
            }

            return '';
        });

        $this->assertEquals('', $result); // No error message, proceed with checkout
    }

    public function testUserWithoutPromoCode()
    {
        $_POST['promocode'] = '';
        $_POST['email'] = 'newuser@example.com';

        // Set up the expectation for the Capsule mock
        $this->capsule->shouldReceive('table->where->where->whereIn->exists')
            ->andReturn(false);

        $result = add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
            $trialProductId = PROMO_TRIAL_PRODUCT_ID;
            $promoCodeApplied = !empty($vars['promocode']);
            $userId = 3; // Simulating a new user with ID 3

            foreach ($_SESSION['cart']['products'] as $product) {
                if ($product['pid'] == $trialProductId) {
                    $hasExistingTrialProduct = Capsule::table('tblhosting')
                        ->where('userid', $userId)
                        ->where('packageid', $trialProductId)
                        ->whereIn('domainstatus', ['Pending', 'Active', 'Suspended', 'Terminated', 'Cancelled', 'Fraud', 'Completed'])
                        ->exists();

                    if ($hasExistingTrialProduct) {
                        return PROMO_TEXT_REQUIRE_PRODUCT;
                    }

                    if (!$promoCodeApplied) {
                        return PROMO_TEXT_DISALLOWED;
                    }
                }
            }

            return '';
        });

        $this->assertEquals(PROMO_TEXT_DISALLOWED, $result);
    }
}
