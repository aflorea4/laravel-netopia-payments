<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Aflorea4\NetopiaPayments\Helpers\RC4Cipher;

// Test data
$testData = '<?xml version="1.0" encoding="utf-8"?><order><signature>NETOPIA</signature><amount>1.00</amount><currency>RON</currency></order>';
$testKey = 'TestKey123';

echo "Testing custom RC4 implementation...\n\n";

// Encrypt the data
echo "Original data: $testData\n\n";
$encryptedData = RC4Cipher::encrypt($testData, $testKey);
echo "Encrypted data (base64): " . base64_encode($encryptedData) . "\n\n";

// Decrypt the data
$decryptedData = RC4Cipher::decrypt($encryptedData, $testKey);
echo "Decrypted data: $decryptedData\n\n";

// Verify the result
if ($decryptedData === $testData) {
    echo "SUCCESS! The custom RC4 implementation works correctly.\n";
} else {
    echo "FAILED! The decrypted data does not match the original data.\n";
}

// Now let's test with the certificates
echo "\n\nTesting with certificates...\n\n";

$publicKeyPath = __DIR__ . '/certs/public.cer';
$privateKeyPath = __DIR__ . '/certs/private.key';

if (!file_exists($publicKeyPath) || !file_exists($privateKeyPath)) {
    echo "Certificate files not found. Please make sure they exist.\n";
    exit(1);
}

// Read the public key
$publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
if ($publicKey === false) {
    echo "Could not read public key.\n";
    exit(1);
}

// Extract the public key details
$keyDetails = openssl_pkey_get_details($publicKey);
if ($keyDetails === false) {
    echo "Could not get public key details.\n";
    exit(1);
}

// Generate a random key for RC4
$rc4Key = openssl_random_pseudo_bytes(16);
echo "Generated RC4 key (base64): " . base64_encode($rc4Key) . "\n\n";

// Encrypt the data with our custom RC4 implementation
$rc4Data = RC4Cipher::encrypt($testData, $rc4Key);
echo "RC4 encrypted data (base64): " . base64_encode($rc4Data) . "\n\n";

// Encrypt the RC4 key with the public key
$encryptedKey = '';
$encryptSuccess = openssl_public_encrypt($rc4Key, $encryptedKey, $keyDetails['key']);
if (!$encryptSuccess) {
    echo "Could not encrypt the RC4 key with the public key.\n";
    exit(1);
}
echo "Encrypted RC4 key (base64): " . base64_encode($encryptedKey) . "\n\n";

// Now decrypt
echo "Decrypting...\n\n";

// Read the private key
$privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
if ($privateKey === false) {
    echo "Could not read private key.\n";
    exit(1);
}

// Decrypt the RC4 key with the private key
$decryptedKey = '';
$decryptSuccess = openssl_private_decrypt($encryptedKey, $decryptedKey, $privateKey);
if (!$decryptSuccess) {
    echo "Could not decrypt the RC4 key with the private key.\n";
    exit(1);
}
echo "Decrypted RC4 key matches original: " . ($decryptedKey === $rc4Key ? "YES" : "NO") . "\n\n";

// Decrypt the data with our custom RC4 implementation
$decryptedData = RC4Cipher::decrypt($rc4Data, $decryptedKey);
echo "Decrypted data: $decryptedData\n\n";

// Verify the result
if ($decryptedData === $testData) {
    echo "SUCCESS! The custom RC4 implementation works correctly with certificates.\n";
} else {
    echo "FAILED! The decrypted data does not match the original data.\n";
}
