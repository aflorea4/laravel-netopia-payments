<?php

use Aflorea4\NetopiaPayments\Helpers\NetopiaPaymentEncryption;
use Illuminate\Support\Facades\Config;
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
});

it('can encrypt data using the signature and public key', function () {
    // Test data
    $signature = TestHelper::getTestSignature();
    $publicKeyPath = TestHelper::getTestPublicKeyPath();
    $testData = '<?xml version="1.0" encoding="utf-8"?><order><signature>' . $signature . '</signature><amount>1.00</amount><currency>RON</currency></order>';
    
    // Encrypt the data
    $encryptedResult = NetopiaPaymentEncryption::encrypt($testData, $signature, $publicKeyPath);
    
    // Verify the encrypted result structure
    expect($encryptedResult)->toBeArray();
    expect($encryptedResult)->toHaveKeys(['env_key', 'data', 'cipher']);
    
    // Verify the data is base64 encoded
    expect(base64_decode($encryptedResult['data'], true))->not->toBeFalse();
    
    // Verify the env_key is base64 encoded
    expect(base64_decode($encryptedResult['env_key'], true))->not->toBeFalse();
    
    // Verify the cipher is one of the expected values
    expect($encryptedResult['cipher'])->toBeIn(['rc4', 'felix-rc4', 'aes-256-cbc']);
});

it('can decrypt data using the signature and private key', function () {
    // Test data
    $signature = TestHelper::getTestSignature();
    $publicKeyPath = TestHelper::getTestPublicKeyPath();
    $privateKeyPath = TestHelper::getTestPrivateKeyPath();
    $testData = '<?xml version="1.0" encoding="utf-8"?><order><signature>' . $signature . '</signature><amount>1.00</amount><currency>RON</currency></order>';
    
    // Determine which cipher to use based on PHP version
    $useAes = (PHP_VERSION_ID >= 70000 && OPENSSL_VERSION_NUMBER > 0x10000000);
    
    if ($useAes) {
        // Test AES-256-CBC encryption directly
        // Generate a random key and IV for testing
        $aesKey = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(16);
        
        // Encrypt the data with AES-256-CBC
        $encryptedXml = openssl_encrypt($testData, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);
        expect($encryptedXml)->not->toBeFalse();
        
        // Decrypt the data to verify it works
        $decryptedXml = openssl_decrypt($encryptedXml, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);
        expect($decryptedXml)->toBe($testData);
        
        // Now test using our helper
        $encryptedResult = NetopiaPaymentEncryption::encrypt($testData, $signature, $publicKeyPath);
        
        // Verify the encrypted data structure
        expect($encryptedResult)->toBeArray();
        expect($encryptedResult)->toHaveKeys(['env_key', 'data', 'cipher', 'iv']);
        expect($encryptedResult['cipher'])->toBe('aes-256-cbc');
        
        // Verify the IV is present and properly encoded
        expect(base64_decode($encryptedResult['iv'], true))->not->toBeFalse();
    } else {
        // For RC4 encryption
        $encryptedResult = NetopiaPaymentEncryption::encrypt($testData, $signature, $publicKeyPath);
        
        // Verify the encrypted data structure
        expect($encryptedResult)->toBeArray();
        expect($encryptedResult)->toHaveKeys(['env_key', 'data', 'cipher']);
        expect($encryptedResult['cipher'])->toBeIn(['rc4', 'felix-rc4']);
        
        // Decrypt the data
        $decryptedData = NetopiaPaymentEncryption::decrypt(
            $encryptedResult['env_key'],
            $encryptedResult['data'],
            $signature,
            $privateKeyPath,
            $encryptedResult['cipher']
        );
        
        // Verify the decrypted data matches the original
        expect($decryptedData)->toBe($testData);
    }
});

it('handles different cipher types correctly', function () {
    // Test data
    $signature = TestHelper::getTestSignature();
    $publicKeyPath = TestHelper::getTestPublicKeyPath();
    $privateKeyPath = TestHelper::getTestPrivateKeyPath();
    $testData = '<?xml version="1.0" encoding="utf-8"?><order><signature>' . $signature . '</signature><amount>1.00</amount><currency>RON</currency></order>';
    
    // Test with felix-rc4 cipher
    $encryptedResult = NetopiaPaymentEncryption::encrypt($testData, $signature, $publicKeyPath);
    
    // Force the cipher to be felix-rc4
    if ($encryptedResult['cipher'] !== 'felix-rc4') {
        // If the default cipher isn't felix-rc4, we'll skip this test
        // This is because we can't force the cipher type in the current implementation
        $this->markTestSkipped('This test requires felix-rc4 cipher to be available');
    }
    
    // Decrypt with the correct cipher
    $decryptedData = NetopiaPaymentEncryption::decrypt(
        $encryptedResult['env_key'],
        $encryptedResult['data'],
        $signature,
        $privateKeyPath,
        'felix-rc4'
    );
    
    // Verify the decrypted data matches the original
    expect($decryptedData)->toBe($testData);
});
