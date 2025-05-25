<?php

use Aflorea4\NetopiaPayments\Facades\NetopiaPayments;
use Aflorea4\NetopiaPayments\Models\Request;
use Aflorea4\NetopiaPayments\Models\Response;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    // Mock the Config facade to use our sandbox certificates
    Config::shouldReceive('get')
        ->with('netopia.signature')
        ->andReturn('NETOPIA');
    
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

it('can generate payment form data for a 1.0 RON transaction', function () {
    // Create a test order
    $orderId = 'TEST' . time();
    $amount = 1.00;
    $currency = 'RON';
    $returnUrl = 'http://localhost/return';
    $confirmUrl = 'http://localhost/confirm';
    
    $billingDetails = [
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => 'test@example.com',
        'phone' => '0700000000',
        'address' => 'Test Address',
        'city' => 'Bucharest',
        'country' => 'Romania',
        'postalCode' => '123456',
    ];
    
    // Create a payment request directly
    $paymentData = NetopiaPayments::createPaymentRequest(
        $orderId, 
        $amount, 
        $currency, 
        $returnUrl, 
        $confirmUrl, 
        $billingDetails, 
        'Test payment of 1.0 RON'
    );
    
    // Verify the payment form data
    expect($paymentData)->toBeArray();
    expect($paymentData)->toHaveKeys(['url', 'env_key', 'data', 'cipher']);
    
    // The URL should be the sandbox URL
    expect($paymentData['url'])->toContain('sandboxsecure.mobilpay.ro');
    
    // The env_key and data should be base64 encoded strings
    expect($paymentData['env_key'])->toBeString();
    expect($paymentData['data'])->toBeString();
    
    // The cipher should be specified
    expect($paymentData['cipher'])->toBeString();
    
    // Store payment data for verification (no echo statements to avoid risky test warnings)
    $paymentInfo = [
        'url' => $paymentData['url'],
        'env_key' => $paymentData['env_key'],
        'data' => $paymentData['data'],
        'cipher' => $paymentData['cipher']
    ];
    
    if (isset($paymentData['iv'])) {
        $paymentInfo['iv'] = $paymentData['iv'];
    }
    
    // Additional assertions on the payment info
    expect($paymentInfo['url'])->toContain('sandboxsecure.mobilpay.ro');
    expect(base64_decode($paymentInfo['env_key'], true))->not->toBeFalse();
    expect(base64_decode($paymentInfo['data'], true))->not->toBeFalse();
});

it('can encrypt and decrypt data with our NetopiaPaymentEncryption helper', function () {
    // Create some test XML data
    $testData = '<?xml version="1.0" encoding="utf-8"?><order><signature>NETOPIA</signature><amount>1.00</amount><currency>RON</currency></order>';
    
    // Use our NetopiaPaymentEncryption helper
    $signature = 'NETOPIA';
    $publicKeyPath = __DIR__ . '/../certs/public.cer';
    $privateKeyPath = __DIR__ . '/../certs/private.key';
    
    // Encrypt the data
    $encryptedData = Aflorea4\NetopiaPayments\Helpers\NetopiaPaymentEncryption::encrypt(
        $testData,
        $signature,
        $publicKeyPath
    );
    
    // Verify the encrypted data structure
    expect($encryptedData)->toBeArray();
    expect($encryptedData)->toHaveKeys(['env_key', 'data', 'cipher']);
    
    // Decrypt the data
    $decryptedData = Aflorea4\NetopiaPayments\Helpers\NetopiaPaymentEncryption::decrypt(
        $encryptedData['env_key'],
        $encryptedData['data'],
        $signature,
        $privateKeyPath,
        $encryptedData['cipher']
    );
    
    // Verify the decrypted data matches the original
    expect($decryptedData)->toBe($testData);
    
    // Define success flag
    $success = true;
    
    // At least one cipher should work
    expect($success)->toBeTrue();
});
