<?php

use PHPUnit\Framework\TestCase;
use WHMCS\Database\Capsule;

class PromoCodeValidationTest extends TestCase
{
    protected function setUp(): void
    {
        // Set up the database connection
        Capsule::schema()->create('tblhosting', function ($table) {
            $table->increments('id');
            $table->integer('userid');
            $table->integer('packageid');
            $table->string('domainstatus');
            $table->string('email')->nullable();
        });

        Capsule::table('tblhosting')->insert([
            ['userid' => 1, 'packageid' => TRIAL_PRODUCT_ID, 'domainstatus' => 'Active'],
            ['userid' => 2, 'packageid' => TRIAL_PRODUCT_ID, 'domainstatus' => 'Cancelled'],
        ]);

        $_SESSION = [
            'cart' => [
                'products' => [['pid' => TRIAL_PRODUCT_ID]],
                'promo' => 'PROMO2023'
            ]
        ];
    }

    protected function tearDown(): void
    {
        // Tear down the database
        Capsule::schema()->drop('tblhosting');
    }

    public function testUserWithExistingTrialProduct()
    {
        $_POST['promocode'] = 'PROMO2023';
        $_POST['email'] = 'test@example.com';

        $result = add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
            $trialProductId = TRIAL_PRODUCT_ID;
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
                        return kt_textRequireProduct;
                    }

                    if (!$promoCodeApplied) {
                        return kt_textDisallowed;
                    }
                }
            }

            return '';
        });

        $this->assertEquals(kt_textRequireProduct, $result);
    }

    public function testUserWithoutExistingTrialProduct()
    {
        $_POST['promocode'] = 'PROMO2023';
        $_POST['email'] = 'newuser@example.com';

        $result = add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
            $trialProductId = TRIAL_PRODUCT_ID;
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
                        return kt_textRequireProduct;
                    }

                    if (!$promoCodeApplied) {
                        return kt_textDisallowed;
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

        $result = add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
            $trialProductId = TRIAL_PRODUCT_ID;
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
                        return kt_textRequireProduct;
                    }

                    if (!$promoCodeApplied) {
                        return kt_textDisallowed;
                    }
                }
            }

            return '';
        });

        $this->assertEquals(kt_textDisallowed, $result);
    }
}
