<?php

use Aflorea4\NetopiaPayments\Facades\NetopiaPayments;
use Aflorea4\NetopiaPayments\Helpers\NetopiaPaymentEncryption;
use Aflorea4\NetopiaPayments\Helpers\NetopiaPaymentHelper;
use Aflorea4\NetopiaPayments\Models\Request;
use Aflorea4\NetopiaPayments\Models\Invoice;
use Aflorea4\NetopiaPayments\Models\BillingAddress;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestHelper;

beforeEach(function () {
    // Mock the Config facade to use our test certificates
    Config::shouldReceive('get')
        ->with('netopia.signature')
        ->andReturn(TestHelper::getTestSignature());
    
    Config::shouldReceive('get')
        ->with('netopia.public_key_path')
        ->andReturn(TestHelper::getTestPublicKeyPath());
    
    Config::shouldReceive('get')
        ->with('netopia.private_key_path')
        ->andReturn(TestHelper::getTestPrivateKeyPath());
    
    Config::shouldReceive('get')
        ->with('netopia.live_mode', false)
        ->andReturn(false);
    
    Config::shouldReceive('get')
        ->with('netopia.default_currency', 'RON')
        ->andReturn('RON');
        
    // Mock additional Config calls that might be needed in GitHub Actions
    Config::shouldReceive('get')
        ->with('logging.channels.deprecations')
        ->andReturn(['driver' => 'null']);
        
    // Allow Config::set calls
    Config::shouldReceive('set')
        ->withAnyArgs()
        ->andReturnNull();
        
    // Catch-all for any other config calls
    Config::shouldReceive('get')
        ->withAnyArgs()
        ->andReturnNull();
        
    // Mock the array access methods (offsetGet, offsetExists, etc.)
    Config::shouldReceive('offsetGet')
        ->withAnyArgs()
        ->andReturnUsing(function ($key) {
            if ($key === 'netopia.signature') return TestHelper::getTestSignature();
            if ($key === 'netopia.public_key_path') return TestHelper::getTestPublicKeyPath();
            if ($key === 'netopia.private_key_path') return TestHelper::getTestPrivateKeyPath();
            if ($key === 'netopia.live_mode') return false;
            if ($key === 'netopia.default_currency') return 'RON';
            if ($key === 'logging.channels.deprecations') return ['driver' => 'null'];
            return null;
        });
        
    Config::shouldReceive('offsetExists')
        ->withAnyArgs()
        ->andReturn(true);
        
    Config::shouldReceive('offsetSet')
        ->withAnyArgs()
        ->andReturnNull();
        
    Config::shouldReceive('offsetUnset')
        ->withAnyArgs()
        ->andReturnNull();
});

it('can encrypt and decrypt data using AES-256-CBC', function () {
    // Skip this test if PHP version is less than 7.0
    if (PHP_VERSION_ID < 70000) {
        $this->markTestSkipped('AES-256-CBC encryption is only supported in PHP 7.0+');
    }
    
    // Skip this test if OpenSSL version is too low
    if (OPENSSL_VERSION_NUMBER <= 0x10000000) {
        $this->markTestSkipped('AES-256-CBC encryption requires OpenSSL > 1.0.0');
    }
    
    // Test with a simple string instead of XML
    $testData = 'This is a test string for AES-256-CBC encryption';
    
    // Generate a random key and IV
    $key = openssl_random_pseudo_bytes(32); // 256 bits for AES-256
    $iv = openssl_random_pseudo_bytes(16);  // 128 bits for AES block size
    
    // Encrypt with OpenSSL directly
    $encrypted = openssl_encrypt($testData, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    expect($encrypted)->not->toBeFalse();
    
    // Decrypt with OpenSSL directly
    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    expect($decrypted)->toBe($testData);
    
    // Now test that our implementation can use AES-256-CBC
    // Create a Netopia Payments instance
    $netopiaPayments = new Aflorea4\NetopiaPayments\NetopiaPayments();
    
    // Create a payment request
    $orderId = 'TEST-AES-ENCRYPT-' . time();
    $amount = 100.00;
    $currency = 'RON';
    $returnUrl = 'https://example.com/return';
    $confirmUrl = 'https://example.com/confirm';
    
    $billingDetails = [
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => 'test@example.com',
    ];
    
    // Generate the payment data
    $paymentData = $netopiaPayments->createPaymentRequest(
        $orderId,
        $amount,
        $currency,
        $returnUrl,
        $confirmUrl,
        $billingDetails,
        'Test AES encryption'
    );
    
    // Verify we're using AES-256-CBC
    expect($paymentData['cipher'])->toBe('aes-256-cbc');
    expect($paymentData)->toHaveKey('iv');
    
    // Verify all data is properly encoded
    expect(base64_decode($paymentData['env_key'], true))->not->toBeFalse();
    expect(base64_decode($paymentData['data'], true))->not->toBeFalse();
    expect(base64_decode($paymentData['iv'], true))->not->toBeFalse();
});

it('verifies payment data structure with AES encryption', function () {
    // Create a Netopia Payments instance
    $netopiaPayments = new Aflorea4\NetopiaPayments\NetopiaPayments();
    
    // Create a payment request
    $orderId = 'TEST-AES-' . time();
    $amount = 100.00;
    $currency = 'RON';
    $returnUrl = 'https://example.com/return';
    $confirmUrl = 'https://example.com/confirm';
    
    $billingDetails = [
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'address' => '123 Test St',
    ];
    
    // Generate the payment data
    $paymentData = $netopiaPayments->createPaymentRequest(
        $orderId,
        $amount,
        $currency,
        $returnUrl,
        $confirmUrl,
        $billingDetails,
        'Test payment with AES'
    );
    
    // Verify the payment data structure
    expect($paymentData)->toBeArray();
    expect($paymentData)->toHaveKeys(['url', 'env_key', 'data', 'cipher']);
    
    // Verify we're using AES-256-CBC
    expect($paymentData['cipher'])->toBe('aes-256-cbc');
    expect($paymentData)->toHaveKey('iv');
    
    // Verify the data is properly encoded
    expect(base64_decode($paymentData['env_key'], true))->not->toBeFalse();
    expect(base64_decode($paymentData['data'], true))->not->toBeFalse();
    expect(base64_decode($paymentData['iv'], true))->not->toBeFalse();
    
    // Verify the URL is for the sandbox environment
    expect($paymentData['url'])->toContain('sandboxsecure.mobilpay.ro');
});

it('can build a valid payment form with AES encryption', function () {
    // Skip if we can't use AES
    if (PHP_VERSION_ID < 70000 || OPENSSL_VERSION_NUMBER <= 0x10000000) {
        $this->markTestSkipped('AES-256-CBC encryption requires PHP 7.0+ and OpenSSL > 1.0.0');
    }
    
    // Create a Netopia Payments instance
    $netopiaPayments = new Aflorea4\NetopiaPayments\NetopiaPayments();
    
    // Create a payment request
    $orderId = 'TEST-FORM-' . time();
    $amount = 100.00;
    $currency = 'RON';
    $returnUrl = 'https://example.com/return';
    $confirmUrl = 'https://example.com/confirm';
    
    $billingDetails = [
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'address' => '123 Test St',
    ];
    
    // Generate the payment data
    $paymentData = $netopiaPayments->createPaymentRequest(
        $orderId,
        $amount,
        $currency,
        $returnUrl,
        $confirmUrl,
        $billingDetails,
        'Test payment form with AES'
    );
    
    // Verify we're using AES-256-CBC
    expect($paymentData['cipher'])->toBe('aes-256-cbc');
    expect($paymentData)->toHaveKey('iv');
    
    // Build a payment form HTML
    $formHtml = '<form id="netopia-form" action="' . $paymentData['url'] . '" method="post">';
    $formHtml .= '<input type="hidden" name="env_key" value="' . $paymentData['env_key'] . '">';
    $formHtml .= '<input type="hidden" name="data" value="' . $paymentData['data'] . '">';
    $formHtml .= '<input type="hidden" name="cipher" value="' . $paymentData['cipher'] . '">';
    
    if (isset($paymentData['iv'])) {
        $formHtml .= '<input type="hidden" name="iv" value="' . $paymentData['iv'] . '">';
    }
    
    $formHtml .= '<button type="submit">Pay Now</button>';
    $formHtml .= '</form>';
    
    // Verify the form contains all required elements
    expect($formHtml)->toContain('action="' . $paymentData['url'] . '"');
    expect($formHtml)->toContain('name="env_key" value="' . $paymentData['env_key'] . '"');
    expect($formHtml)->toContain('name="data" value="' . $paymentData['data'] . '"');
    expect($formHtml)->toContain('name="cipher" value="aes-256-cbc"');
    expect($formHtml)->toContain('name="iv" value="' . $paymentData['iv'] . '"');
});
