<?php

/**
 * Promo Code Validation Tests
 * 
 * Author: Carlos Maundu
 */

use PHPUnit\Framework\TestCase;
use Mockery as m;

class PromoCodeValidationTest extends TestCase
{
    protected $capsule;

    protected function setUp(): void
    {
        // Mock the Capsule class and its methods
        $this->capsule = m::mock('alias:WHMCS\Database\Capsule');
        
        // Define constants used in the hook file if not already defined
        if (!defined('PROMO_TRIAL_PRODUCT_ID')) {
            define('PROMO_TRIAL_PRODUCT_ID', 49);
        }
        if (!defined('PROMO_PROMPT_REMOVAL')) {
            define('PROMO_PROMPT_REMOVAL', 'modal');
        }
        if (!defined('PROMO_TEXT_DISALLOWED')) {
            define('PROMO_TEXT_DISALLOWED', 'You must apply a promo code to checkout with a trial product.');
        }
        if (!defined('PROMO_TEXT_REQUIRE_PRODUCT')) {
            define('PROMO_TEXT_REQUIRE_PRODUCT', 'You cannot use a promo code for this trial product again.');
        }

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

    public function test_no_trial_product_in_cart()
    {
        $_SESSION['cart']['products'] = []; // No products in cart
        $vars = ['clientId' => 1, 'promocode' => 'PROMO123'];
        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) {})(['clientId' => 1, 'promocode' => 'PROMO123']);
        $this->assertEquals('', $result);
    }

    public function test_null_client_id_handling()
    {
        $_SESSION['cart']['products'] = [['pid' => PROMO_TRIAL_PRODUCT_ID]]; // Trial product in cart
        $vars = ['clientId' => null, 'promocode' => ''];
        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) {})(['clientId' => null, 'promocode' => '']);
        $this->assertEquals(PROMO_TEXT_DISALLOWED, $result);
    }

    public function test_shopping_cart_validate_checkout_returns_empty_string_when_promo_code_applied_and_no_previous_trial_product_usage()
    {
        $_SESSION['cart']['products'] = [['pid' => PROMO_TRIAL_PRODUCT_ID]]; // Trial product in cart
        $vars = ['clientId' => 1, 'promocode' => 'PROMO123', 'email' => 'test@example.com'];
        $this->capsule->shouldReceive('table->where->where->whereIn->exists')->andReturn(false);
        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) {})(['clientId' => 1, 'promocode' => 'PROMO123', 'email' => 'test@example.com']);
        $this->assertEquals('', $result);
    }

    public function test_checkout_allowed_with_promo_code()
    {
        $_SESSION['cart']['products'] = [['pid' => PROMO_TRIAL_PRODUCT_ID]];
        $userId = 1;
        $promoCodeApplied = true;
    
        $mockCapsule = $this->getMockBuilder('Capsule')->getMock();
        $mockCapsule->method('table')->willReturn($mockCapsule);
        $mockCapsule->method('where')->willReturn($mockCapsule);
        $mockCapsule->method('whereIn')->willReturn($mockCapsule);
        $mockCapsule->method('exists')->willReturn(false);
    
        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) use ($mockCapsule) {
            return function($vars) use ($mockCapsule) {
                return $vars['clientId'] == 1 ? $mockCapsule->exists() : false;
            };
        })(['clientId' => $userId, 'promocode' => 'PROMO123', 'email' => 'test@example.com']);
    
        $this->assertEquals('', $result);
    }

    public function test_promo_code_missing_for_trial_product()
    {
        $_SESSION['cart']['products'] = [['pid' => PROMO_TRIAL_PRODUCT_ID]]; // Trial product in cart
        $vars = ['clientId' => 1, 'promocode' => '']; // Promo code missing
        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) {})(['clientId' => 1, 'promocode' => '']);
        $this->assertEquals(PROMO_TEXT_DISALLOWED, $result);
    }

    public function test_shopping_cart_validate_checkout_returns_error_when_trial_product_reused_by_same_user()
    {
        $_SESSION['cart']['products'] = [['pid' => PROMO_TRIAL_PRODUCT_ID]]; // Product in cart
        $userId = 1;
        $vars = ['clientId' => $userId, 'promocode' => 'PROMO123'];

        // Mock the Capsule class
        $capsuleMock = $this->getMockBuilder('Capsule')
            ->setMethods(['table', 'where', 'whereIn', 'exists'])
            ->getMock();

        $capsuleMock->expects($this->once())
            ->method('table')
            ->willReturnSelf();

        $capsuleMock->expects($this->once())
            ->method('where')
            ->with('userid', $userId)
            ->willReturnSelf();

        $capsuleMock->expects($this->once())
            ->method('whereIn')
            ->with('domainstatus', ['Pending', 'Active', 'Suspended', 'Terminated', 'Cancelled', 'Fraud', 'Completed'])
            ->willReturnSelf();

        $capsuleMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $this->replaceInstance(Capsule::class, $capsuleMock);

        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) {})(['clientId' => $userId, 'promocode' => 'PROMO123']);
        $this->assertEquals(PROMO_TEXT_REQUIRE_PRODUCT, $result);
    }

    public function test_trial_product_usage_across_statuses_except_cancelled()
    {
        $_SESSION['cart']['products'] = [
            ['pid' => PROMO_TRIAL_PRODUCT_ID]
        ];
        $this->capsule->shouldReceive('table')->andReturnSelf();
        $this->capsule->shouldReceive('where')->andReturnSelf();
        $this->capsule->shouldReceive('whereIn')->andReturnSelf();
        $this->capsule->shouldReceive('exists')->andReturn(true);

        $vars = ['clientId' => 1, 'promocode' => 'PROMO123'];
        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) {})(['clientId' => 1, 'promocode' => 'PROMO123']);
        $this->assertEquals(PROMO_TEXT_REQUIRE_PRODUCT, $result);
    }

    public function test_shopping_cart_validate_checkout_with_email()
    {
        $_SESSION['cart']['products'] = [['pid' => PROMO_TRIAL_PRODUCT_ID]]; // Product in cart
        $vars = ['email' => 'test@example.com', 'promocode' => 'PROMO123'];
        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) {})(['email' => 'test@example.com', 'promocode' => 'PROMO123']);
        $this->assertEquals('', $result);
    }

    public function test_handle_multiple_trial_products_in_cart()
    {
        $_SESSION['cart']['products'] = [
            ['pid' => PROMO_TRIAL_PRODUCT_ID],
            ['pid' => PROMO_TRIAL_PRODUCT_ID],
            ['pid' => 50],
        ];
        $vars = ['clientId' => 1, 'promocode' => 'PROMO123'];
        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) {})(['clientId' => 1, 'promocode' => 'PROMO123']);
        $this->assertEquals('', $result);
    }

    public function test_no_trial_product_with_missing_email_and_clientId()
    {
        $_SESSION['cart']['products'] = [['pid' => 50]]; // Product in cart, but not trial product
        $vars = ['promocode' => 'PROMO123'];
        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) {})(['promocode' => 'PROMO123']);
        $this->assertEquals('', $result);
    }

    public function test_handle_database_query_failure()
    {
        $_SESSION['cart']['products'] = [['pid' => PROMO_TRIAL_PRODUCT_ID]]; // Product in cart
        $vars = ['clientId' => 1, 'promocode' => 'PROMO123', 'email' => 'test@example.com'];
        $mockCapsule = $this->getMockBuilder('Capsule')->getMock();
        $mockCapsule->method('table')->willThrowException(new Exception('Database query failed'));
        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) use ($mockCapsule) {
            Capsule::swap($mockCapsule);
        })(['clientId' => 1, 'promocode' => 'PROMO123', 'email' => 'test@example.com']);
        $this->assertEquals('', $result);
    }

    public function test_handle_invalid_product_ids_in_cart()
    {
        $_SESSION['cart']['products'] = [['pid' => 50]]; // Invalid product ID in cart
        $vars = ['clientId' => 1, 'promocode' => 'PROMO123'];
        $result = add_hook('ShoppingCartValidateCheckout', 1, function($vars) {})(['clientId' => 1, 'promocode' => 'PROMO123']);
        $this->assertEquals('', $result);
    }
}
