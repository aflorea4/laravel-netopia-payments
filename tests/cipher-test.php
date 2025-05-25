<?php

/**
 * Simple standalone test script for Netopia encryption/decryption
 * This script tests different cipher algorithms to find one that works with your certificates
 */

// Configuration
$publicKeyPath = __DIR__ . '/certs/public.cer';
$privateKeyPath = __DIR__ . '/certs/private.key';

// Test data to encrypt/decrypt
$testData = '<?xml version="1.0" encoding="utf-8"?><order><signature>NETOPIA</signature><amount>1.00</amount><currency>RON</currency></order>';

// Get available cipher methods
$availableCiphers = openssl_get_cipher_methods();
echo "Available cipher methods: " . count($availableCiphers) . "\n";

// Filter to common ciphers that might work with Netopia
$testCiphers = array_filter($availableCiphers, function($cipher) {
    return 
        strpos($cipher, 'aes') !== false || 
        strpos($cipher, 'des') !== false || 
        $cipher === 'rc4' || 
        strpos($cipher, 'camellia') !== false;
});

echo "\nTesting " . count($testCiphers) . " cipher methods...\n";

// Test each cipher
$workingCiphers = [];

foreach ($testCiphers as $cipher) {
    echo "\nTesting cipher: $cipher\n";
    
    try {
        // Read the public key
        $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
        if ($publicKey === false) {
            echo "  Could not read public key\n";
            continue;
        }
        
        // Encrypt the data
        $encryptedData = '';
        $envKeys = [];
        
        // Generate IV if needed
        $iv = null;
        $ivLength = 0;
        
        try {
            $ivLength = openssl_cipher_iv_length($cipher);
            if ($ivLength > 0 && $cipher !== 'rc4') {
                $iv = openssl_random_pseudo_bytes($ivLength);
            }
        } catch (Exception $e) {
            echo "  Error getting IV length for $cipher: " . $e->getMessage() . "\n";
            continue;
        }
        
        // Try to encrypt with this cipher
        if (!openssl_seal($testData, $encryptedData, $envKeys, [$publicKey], $cipher, $iv)) {
            echo "  Could not encrypt with $cipher\n";
            continue;
        }
        
        // Free the key
        openssl_free_key($publicKey);
        
        // Prepare the encrypted data
        $encData = [
            'env_key' => base64_encode($envKeys[0]),
            'data' => base64_encode($encryptedData),
            'cipher' => $cipher,
        ];
        
        // Add IV if used
        if ($iv !== null && $ivLength > 0) {
            $encData['iv'] = base64_encode($iv);
        }
        
        echo "  Encryption successful!\n";
        
        // Try to decrypt the data
        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        if ($privateKey === false) {
            echo "  Could not read private key\n";
            continue;
        }
        
        // Decode the data
        $envKey = base64_decode($encData['env_key']);
        $data = base64_decode($encData['data']);
        $ivDecrypt = isset($encData['iv']) ? base64_decode($encData['iv']) : null;
        
        // Decrypt the data
        $decryptedData = '';
        if (!openssl_open($data, $decryptedData, $envKey, $privateKey, $cipher, $ivDecrypt)) {
            echo "  Could not decrypt with $cipher\n";
            continue;
        }
        
        // Free the key
        openssl_free_key($privateKey);
        
        // Verify the decrypted data matches the original
        if ($decryptedData === $testData) {
            echo "  SUCCESS! Decryption successful with $cipher\n";
            $workingCiphers[] = $cipher;
        } else {
            echo "  Decryption produced different data with $cipher\n";
        }
    } catch (Exception $e) {
        echo "  Error with $cipher: " . $e->getMessage() . "\n";
    }
}

// Summary
echo "\n=== SUMMARY ===\n";
if (count($workingCiphers) > 0) {
    echo "The following ciphers worked successfully:\n";
    foreach ($workingCiphers as $cipher) {
        echo "- $cipher\n";
    }
    echo "\nRecommended cipher to use: " . $workingCiphers[0] . "\n";
} else {
    echo "No ciphers worked successfully. Please check your certificates and PHP configuration.\n";
}
