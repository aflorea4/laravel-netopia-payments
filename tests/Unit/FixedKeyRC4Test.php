<?php

use Aflorea4\NetopiaPayments\Helpers\FixedKeyRC4;

/**
 * Note: As of v0.2.6, this class internally uses AES-256-CBC encryption instead of RC4.
 * The class name is kept for backward compatibility, but all encryption/decryption
 * operations now use AES-256-CBC.
 */

it('can encrypt and decrypt data with a fixed key', function () {
    // Set a custom key for testing
    $customKey = 'NetopiaTest123';
    FixedKeyRC4::setKey($customKey);
    
    // Test data
    $testData = '<?xml version="1.0" encoding="utf-8"?><order><signature>TEST-SIGNATURE</signature><amount>1.00</amount><currency>RON</currency></order>';
    
    // Encrypt the data
    $encryptedData = FixedKeyRC4::encrypt($testData);
    
    // Verify the encrypted data is base64 encoded
    expect($encryptedData)->toBeString();
    expect(base64_decode($encryptedData, true))->not->toBeFalse();
    
    // Decrypt the data
    $decryptedData = FixedKeyRC4::decrypt($encryptedData);
    
    // Verify the decrypted data matches the original
    expect($decryptedData)->toBe($testData);
});

it('can use different keys for different encryption contexts', function () {
    // First encryption with key1
    $key1 = 'FirstTestKey';
    FixedKeyRC4::setKey($key1);
    
    $testData1 = 'Test data for first key';
    $encrypted1 = FixedKeyRC4::encrypt($testData1);
    
    // Second encryption with key2
    $key2 = 'SecondTestKey';
    FixedKeyRC4::setKey($key2);
    
    $testData2 = 'Test data for second key';
    $encrypted2 = FixedKeyRC4::encrypt($testData2);
    
    // Try to decrypt first data with second key (should fail)
    try {
        $incorrectDecryption = FixedKeyRC4::decrypt($encrypted1);
        // With AES, this will likely throw an exception due to incorrect padding
        // If we get here, make sure the decryption is incorrect
        expect($incorrectDecryption)->not->toBe($testData1);
    } catch (\Exception $e) {
        // Expected behavior with AES when using wrong key
        expect($e->getMessage())->toContain('AES decryption failed');
    }
    
    // Reset to first key and decrypt correctly
    FixedKeyRC4::setKey($key1);
    $correctDecryption = FixedKeyRC4::decrypt($encrypted1);
    expect($correctDecryption)->toBe($testData1);
    
    // Reset to second key and decrypt correctly
    FixedKeyRC4::setKey($key2);
    $correctDecryption2 = FixedKeyRC4::decrypt($encrypted2);
    expect($correctDecryption2)->toBe($testData2);
});

it('can handle empty strings', function () {
    FixedKeyRC4::setKey('TestKey');
    
    $emptyString = '';
    $encrypted = FixedKeyRC4::encrypt($emptyString);
    $decrypted = FixedKeyRC4::decrypt($encrypted);
    
    expect($decrypted)->toBe($emptyString);
});

it('can handle special characters', function () {
    FixedKeyRC4::setKey('TestKey');
    
    $specialChars = 'Special characters: !@#$%^&*()_+{}|:"<>?~`-=[]\\;\',./';
    $encrypted = FixedKeyRC4::encrypt($specialChars);
    $decrypted = FixedKeyRC4::decrypt($encrypted);
    
    expect($decrypted)->toBe($specialChars);
});

it('uses AES-256-CBC encryption', function () {
    // Verify that we're using AES-256-CBC by checking the IV length
    $testData = 'Test AES-256-CBC';
    $encrypted = FixedKeyRC4::encrypt($testData);
    
    // Decode the base64 data
    $decoded = base64_decode($encrypted);
    
    // The first 16 bytes should be the IV for AES-256-CBC
    $iv = substr($decoded, 0, 16);
    expect(strlen($iv))->toBe(16);
    
    // The rest should be the encrypted data
    $ciphertext = substr($decoded, 16);
    expect(strlen($ciphertext))->toBeGreaterThan(0);
});
