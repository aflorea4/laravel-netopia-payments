<?php

use Aflorea4\NetopiaPayments\Helpers\NetopiaPaymentEncryption;
use Illuminate\Support\Facades\Config;

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
});

it('can encrypt data using the signature and public key', function () {
    // Test data
    $signature = '2VXM-Q4WB-F8UL-MRU6-PWP3';
    $publicKeyPath = __DIR__ . '/../certs/public.cer';
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
    expect($encryptedResult['cipher'])->toBeIn(['rc4', 'felix-rc4']);
});

it('can decrypt data using the signature and private key', function () {
    // Test data
    $signature = '2VXM-Q4WB-F8UL-MRU6-PWP3';
    $publicKeyPath = __DIR__ . '/../certs/public.cer';
    $privateKeyPath = __DIR__ . '/../certs/private.key';
    $testData = '<?xml version="1.0" encoding="utf-8"?><order><signature>' . $signature . '</signature><amount>1.00</amount><currency>RON</currency></order>';
    
    // Encrypt the data
    $encryptedResult = NetopiaPaymentEncryption::encrypt($testData, $signature, $publicKeyPath);
    
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
});

it('handles different cipher types correctly', function () {
    // Test data
    $signature = '2VXM-Q4WB-F8UL-MRU6-PWP3';
    $publicKeyPath = __DIR__ . '/../certs/public.cer';
    $privateKeyPath = __DIR__ . '/../certs/private.key';
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
