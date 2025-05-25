<?php

use Aflorea4\NetopiaPayments\Facades\NetopiaPayments;
use Aflorea4\NetopiaPayments\Helpers\NetopiaPaymentEncryption;
use Aflorea4\NetopiaPayments\Helpers\NetopiaPaymentHelper;
use Aflorea4\NetopiaPayments\Models\Request;
use Aflorea4\NetopiaPayments\Models\Invoice;
use Aflorea4\NetopiaPayments\Models\BillingAddress;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Mock the Config facade to use our test certificates
    Config::shouldReceive('get')
        ->with('netopia.signature')
        ->andReturn('2VXM-Q4WB-F8UL-MRU6-PWP3');
    
    Config::shouldReceive('get')
        ->with('netopia.public_key_path')
        ->andReturn(__DIR__ . '/../certs/public.cer');
    
    Config::shouldReceive('get')
        ->with('netopia.private_key_path')
        ->andReturn(__DIR__ . '/../certs/private.key');
    
    Config::shouldReceive('get')
        ->with('netopia.live_mode', false)
        ->andReturn(false);
    
    Config::shouldReceive('get')
        ->with('netopia.default_currency', 'RON')
        ->andReturn('RON');
});

it('can generate a payment redirect URL for a 1.00 RON transaction', function () {
    // Create a test order
    $orderId = 'TEST-' . time();
    $amount = 1.00;
    $currency = 'RON';
    $returnUrl = 'https://example.com/return';
    $confirmUrl = 'https://example.com/confirm';
    
    // Create billing details
    $billingDetails = [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '1234567890',
        'address' => '123 Main St',
        'city' => 'Bucharest',
        'country' => 'Romania',
        'postalCode' => '123456',
    ];
    
    // Create a payment request using the facade
    $paymentData = NetopiaPayments::createPaymentRequest(
        $orderId, 
        $amount, 
        $currency, 
        $returnUrl, 
        $confirmUrl, 
        $billingDetails, 
        'Test payment of 1.00 RON'
    );
    
    // Verify the payment data structure
    expect($paymentData)->toBeArray();
    expect($paymentData)->toHaveKeys(['url', 'env_key', 'data', 'cipher']);
    
    // The URL should be the sandbox URL
    expect($paymentData['url'])->toContain('sandboxsecure.mobilpay.ro');
    
    // The env_key and data should be base64 encoded strings
    expect($paymentData['env_key'])->toBeString();
    expect($paymentData['data'])->toBeString();
    
    // The cipher should be specified
    expect($paymentData['cipher'])->toBeString();
    
    // Construct the full redirect URL
    $redirectUrl = $paymentData['url'] . '/payment/card/payment-request';
    
    // Instead of echoing, we'll just assert the values
    // This avoids the "risky" test warning
    
    // Verify we can build a form that would redirect to Netopia
    $formHtml = <<<HTML
    <form action="$redirectUrl" method="post" id="netopiaPaymentForm">
        <input type="hidden" name="env_key" value="{$paymentData['env_key']}">
        <input type="hidden" name="data" value="{$paymentData['data']}">
        <input type="hidden" name="cipher" value="{$paymentData['cipher']}">
        <button type="submit">Pay Now</button>
    </form>
    HTML;
    
    expect($formHtml)->toContain('netopiaPaymentForm');
    expect($formHtml)->toContain($paymentData['env_key']);
    expect($formHtml)->toContain($paymentData['data']);
    expect($formHtml)->toContain($paymentData['cipher']);
});

it('can encrypt and decrypt payment data using our RC4 implementation', function () {
    // Get the signature and key paths from the config
    $signature = Config::get('netopia.signature');
    $publicKeyPath = Config::get('netopia.public_key_path');
    $privateKeyPath = Config::get('netopia.private_key_path');
    
    // Create test payment data
    $testData = '<?xml version="1.0" encoding="utf-8"?><order><signature>' . $signature . '</signature><amount>1.00</amount><currency>RON</currency></order>';
    
    // Encrypt the data using our implementation
    $encryptedData = NetopiaPaymentEncryption::encrypt($testData, $signature, $publicKeyPath);
    
    // Verify the encrypted data structure
    expect($encryptedData)->toBeArray();
    expect($encryptedData)->toHaveKeys(['env_key', 'data', 'cipher']);
    
    // The cipher should be 'felix-rc4' or 'rc4'
    expect($encryptedData['cipher'])->toBeIn(['felix-rc4', 'rc4']);
    
    // Decrypt the data
    $decryptedData = NetopiaPaymentEncryption::decrypt(
        $encryptedData['env_key'],
        $encryptedData['data'],
        $signature,
        $privateKeyPath,
        $encryptedData['cipher']
    );
    
    // Verify the decrypted data matches the original
    expect($decryptedData)->toBe($testData);
    
    // Store test information in variables instead of echoing
    $originalData = $testData;
    $cipherUsed = $encryptedData['cipher'];
    $decryptedResult = $decryptedData;
    $isMatch = ($decryptedData === $testData);
    
    // Assert the match explicitly
    expect($isMatch)->toBeTrue();
});

it('verifies payment data format for a 1.00 RON transaction', function () {
    // Create a test order
    $orderId = 'TEST-' . time();
    $amount = 1.00;
    $currency = 'RON';
    $returnUrl = 'https://example.com/return';
    $confirmUrl = 'https://example.com/confirm';
    
    // Create billing details
    $billingDetails = [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '1234567890',
        'address' => '123 Main St',
        'city' => 'Bucharest',
        'country' => 'Romania',
        'postalCode' => '123456',
    ];
    
    // Create a payment request using the facade
    $paymentData = NetopiaPayments::createPaymentRequest(
        $orderId, 
        $amount, 
        $currency, 
        $returnUrl, 
        $confirmUrl, 
        $billingDetails, 
        'Test payment of 1.00 RON'
    );
    
    // Construct the full redirect URL
    $redirectUrl = $paymentData['url'] . '/payment/card/payment-request';
    
    // Verify the payment data format
    expect($redirectUrl)->toContain('sandboxsecure.mobilpay.ro');
    expect($paymentData['env_key'])->toBeString();
    expect($paymentData['data'])->toBeString();
    expect($paymentData['cipher'])->toBeString();
    
    // Verify that the env_key is base64 encoded
    $decodedEnvKey = base64_decode($paymentData['env_key'], true);
    expect($decodedEnvKey)->not->toBeFalse();
    
    // Verify that the data is base64 encoded
    $decodedData = base64_decode($paymentData['data'], true);
    expect($decodedData)->not->toBeFalse();
    
    // Verify that the cipher is one of the expected values
    expect($paymentData['cipher'])->toBeIn(['rc4', 'felix-rc4', 'fixed-rc4']);
    
    // Simulate form submission by creating the form data array
    $formData = [
        'env_key' => $paymentData['env_key'],
        'data' => $paymentData['data'],
        'cipher' => $paymentData['cipher'],
    ];
    
    // Instead of echoing, we'll just assert the values
    // This avoids the "risky" test warning
    
    // Verify that the form data is complete
    expect($formData)->toHaveKeys(['env_key', 'data', 'cipher']);
});

it('can generate a complete payment form for a 1.00 RON transaction', function () {
    // Create a test request
    $request = new Request();
    $request->signature = Config::get('netopia.signature');
    $request->orderId = 'TEST-' . time();
    $request->returnUrl = 'https://example.com/return';
    $request->confirmUrl = 'https://example.com/confirm';
    
    // Create a billing address
    $billingAddress = new BillingAddress();
    $billingAddress->type = 'person';
    $billingAddress->firstName = 'John';
    $billingAddress->lastName = 'Doe';
    $billingAddress->email = 'john.doe@example.com';
    $billingAddress->address = '123 Main St';
    $billingAddress->mobilePhone = '1234567890';
    
    // Create an invoice
    $invoice = new Invoice();
    $invoice->amount = 1.00;
    $invoice->currency = 'RON';
    $invoice->details = 'Test payment';
    $invoice->billingAddress = $billingAddress;
    
    // Set the invoice in the request
    $request->invoice = $invoice;
    
    // Generate payment form data
    $paymentFormData = NetopiaPaymentHelper::generatePaymentFormData(
        $request, 
        Config::get('netopia.signature'), 
        Config::get('netopia.public_key_path'),
        false // Use sandbox mode
    );
    
    // Verify the payment form data
    expect($paymentFormData)->toBeArray();
    expect($paymentFormData)->toHaveKeys(['env_key', 'data', 'cipher', 'url']);
    
    // The URL should be the sandbox URL
    expect($paymentFormData['url'])->toContain('sandboxsecure.mobilpay.ro');
    
    // The cipher should be specified
    expect($paymentFormData['cipher'])->toBeString();
    
    // Build the full payment URL
    $paymentUrl = $paymentFormData['url'] . '/payment/card/payment-request';
    
    // Build the form HTML
    $formHtml = '<form action="' . $paymentUrl . '" method="post" id="netopiaPaymentForm">';
    $formHtml .= '<input type="hidden" name="env_key" value="' . $paymentFormData['env_key'] . '">';
    $formHtml .= '<input type="hidden" name="data" value="' . $paymentFormData['data'] . '">';
    $formHtml .= '<input type="hidden" name="cipher" value="' . $paymentFormData['cipher'] . '">';
    $formHtml .= '<button type="submit">Pay Now</button>';
    $formHtml .= '</form>';
    
    // Verify the form HTML
    expect($formHtml)->toContain($paymentUrl);
    expect($formHtml)->toContain($paymentFormData['env_key']);
    expect($formHtml)->toContain($paymentFormData['data']);
    expect($formHtml)->toContain($paymentFormData['cipher']);
    
    // Store the form HTML in a variable instead of echoing
    $htmlOutput = $formHtml;
    
    // Additional assertions to verify the form structure
    expect($htmlOutput)->toContain('<form');
    expect($htmlOutput)->toContain('</form>');
    expect($htmlOutput)->toContain('method="post"');
    expect($htmlOutput)->toContain('type="hidden"');
});
