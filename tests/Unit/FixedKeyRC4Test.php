<?php

use Aflorea4\NetopiaPayments\Helpers\FixedKeyRC4;

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
    $incorrectDecryption = FixedKeyRC4::decrypt($encrypted1);
    expect($incorrectDecryption)->not->toBe($testData1);
    
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
