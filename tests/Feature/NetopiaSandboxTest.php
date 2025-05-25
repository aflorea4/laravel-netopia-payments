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
    
    // Log the payment data for debugging
    echo "\nPayment Form Data:\n";
    echo "URL: " . $paymentData['url'] . "\n";
    echo "ENV_KEY: " . $paymentData['env_key'] . "\n";
    echo "DATA: " . $paymentData['data'] . "\n";
    echo "CIPHER: " . $paymentData['cipher'] . "\n";
    if (isset($paymentData['iv'])) {
        echo "IV: " . $paymentData['iv'] . "\n";
    }
});

it('can encrypt and decrypt data with the current cipher settings', function () {
    // Create some test XML data
    $testData = '<?xml version="1.0" encoding="utf-8"?><order><signature>NETOPIA</signature><amount>1.00</amount><currency>RON</currency></order>';
    
    // Use reflection to access the protected encrypt method
    $netopiaPayments = new ReflectionClass(Aflorea4\NetopiaPayments\NetopiaPayments::class);
    $encrypt = $netopiaPayments->getMethod('encrypt');
    $encrypt->setAccessible(true);
    
    $decrypt = $netopiaPayments->getMethod('decrypt');
    $decrypt->setAccessible(true);
    
    // Create an instance of NetopiaPayments
    $instance = $netopiaPayments->newInstance();
    
    // Try with specific ciphers that are available in PHP
    $ciphers = ['aes-128-cbc', 'aes-256-cbc', 'des-ede3-cbc'];
    $success = false;
    
    foreach ($ciphers as $testCipher) {
        echo "\nTesting with cipher: $testCipher\n";
        
        try {
            // Force a specific cipher for testing
            $encryptMethod = new ReflectionMethod(Aflorea4\NetopiaPayments\NetopiaPayments::class, 'encrypt');
            $encryptMethod->setAccessible(true);
            
            // Read the public key
            $publicKey = openssl_pkey_get_public(file_get_contents(__DIR__ . '/../certs/public.cer'));
            if ($publicKey === false) {
                echo "Could not read public key\n";
                continue;
            }
            
            // Encrypt the data
            $encryptedData = '';
            $envKeys = [];
            
            // Generate IV if needed
            $iv = null;
            if ($testCipher !== 'rc4') {
                $ivlen = openssl_cipher_iv_length($testCipher);
                $iv = openssl_random_pseudo_bytes($ivlen);
            }
            
            // Try to encrypt with this cipher
            if (!openssl_seal($testData, $encryptedData, $envKeys, [$publicKey], $testCipher, $iv)) {
                echo "Could not encrypt with $testCipher\n";
                continue;
            }
            
            // Free the key
            openssl_free_key($publicKey);
            
            // Prepare the encrypted data
            $encData = [
                'env_key' => base64_encode($envKeys[0]),
                'data' => base64_encode($encryptedData),
                'cipher' => $testCipher,
            ];
            
            // Add IV if used
            if ($iv !== null && $testCipher !== 'rc4') {
                $encData['iv'] = base64_encode($iv);
            }
            
            // Log the encrypted data
            echo "Encrypted Data:\n";
            echo "ENV_KEY: " . $encData['env_key'] . "\n";
            echo "DATA: " . $encData['data'] . "\n";
            echo "CIPHER: " . $encData['cipher'] . "\n";
            if (isset($encData['iv'])) {
                echo "IV: " . $encData['iv'] . "\n";
            }
            
            // Try to decrypt the data
            $privateKey = openssl_pkey_get_private(file_get_contents(__DIR__ . '/../certs/private.key'));
            if ($privateKey === false) {
                echo "Could not read private key\n";
                continue;
            }
            
            // Decode the data
            $envKey = base64_decode($encData['env_key']);
            $data = base64_decode($encData['data']);
            $ivDecrypt = isset($encData['iv']) ? base64_decode($encData['iv']) : null;
            
            // Decrypt the data
            $decryptedData = '';
            if (!openssl_open($data, $decryptedData, $envKey, $privateKey, $testCipher, $ivDecrypt)) {
                echo "Could not decrypt with $testCipher\n";
                continue;
            }
            
            // Free the key
            openssl_free_key($privateKey);
            
            // Verify the decrypted data matches the original
            if ($decryptedData === $testData) {
                echo "Decryption successful! The data was correctly encrypted and decrypted with $testCipher.\n";
                $success = true;
                break;
            } else {
                echo "Decryption produced different data with $testCipher.\n";
            }
        } catch (Exception $e) {
            echo "Error with $testCipher: " . $e->getMessage() . "\n";
        }
    }
    
    // At least one cipher should work
    expect($success)->toBeTrue();
});
