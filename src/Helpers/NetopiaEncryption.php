<?php

namespace Aflorea4\NetopiaPayments\Helpers;

use Exception;
use Felix\RC4\RC4 as FelixRC4;

/**
 * Netopia Encryption Helper
 * This class provides encryption and decryption methods compatible with Netopia Payments
 */
class NetopiaEncryption
{
    /**
     * Encrypt data using RC4 algorithm
     *
     * @param string $data The data to encrypt
     * @param string $publicKeyPath Path to the public key file
     * @return array The encrypted data with envelope key and cipher info
     * @throws Exception
     */
    public static function encrypt($data, $publicKeyPath)
    {
        // Read the public key
        $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
        if ($publicKey === false) {
            throw new Exception('Could not read public key');
        }
        
        try {
            // Check if built-in RC4 is available
            if (in_array('rc4', openssl_get_cipher_methods())) {
                $sealed = '';
                $envKeys = [];
                
                // Try to encrypt with RC4
                $encryptSuccess = openssl_seal($data, $sealed, $envKeys, [$publicKey], 'RC4');
                
                if ($encryptSuccess) {
                    return [
                        'env_key' => base64_encode($envKeys[0]),
                        'data' => base64_encode($sealed),
                        'cipher' => 'rc4',
                    ];
                }
            }
            
            // If built-in RC4 fails or is not available, use Felix RC4
            // Generate a random RC4 key (16 bytes)
            $rc4Key = openssl_random_pseudo_bytes(16);
            
            // Encrypt the data with Felix RC4 implementation
            $encryptedData = FelixRC4::rc4($rc4Key, $data);
            
            // Encrypt the RC4 key with the public key
            $encryptedKey = '';
            if (!openssl_public_encrypt($rc4Key, $encryptedKey, $publicKey)) {
                throw new Exception('Could not encrypt RC4 key with public key');
            }
            
            // Return the encrypted data
            return [
                'env_key' => base64_encode($encryptedKey),
                'data' => base64_encode($encryptedData),
                'cipher' => 'felix-rc4', // Mark as Felix RC4 implementation
            ];
        } finally {
            // Free the key
            @openssl_free_key($publicKey);
        }
    }
    
    /**
     * Decrypt data using RC4 algorithm
     *
     * @param string $encryptedKey The encrypted RC4 key (base64 encoded)
     * @param string $encryptedData The encrypted data (base64 encoded)
     * @param string $privateKeyPath Path to the private key file
     * @param string $cipher The cipher used for encryption
     * @return string The decrypted data
     * @throws Exception
     */
    public static function decrypt($encryptedKey, $encryptedData, $privateKeyPath, $cipher = 'rc4')
    {
        // Decode the base64 encoded data
        $encryptedKey = base64_decode($encryptedKey);
        $encryptedData = base64_decode($encryptedData);
        
        // Read the private key
        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        if ($privateKey === false) {
            throw new Exception('Could not read private key');
        }
        
        try {
            // Handle built-in RC4
            if ($cipher === 'rc4' && in_array('rc4', openssl_get_cipher_methods())) {
                $decryptedData = '';
                if (openssl_open($encryptedData, $decryptedData, $encryptedKey, $privateKey, 'rc4')) {
                    return $decryptedData;
                }
            }
            
            // Handle Felix RC4 or fallback for built-in RC4
            // Decrypt the RC4 key with the private key
            $rc4Key = '';
            if (!openssl_private_decrypt($encryptedKey, $rc4Key, $privateKey)) {
                throw new Exception('Could not decrypt the envelope key');
            }
            
            // Decrypt the data with Felix RC4 implementation
            return FelixRC4::rc4($rc4Key, $encryptedData);
        } finally {
            // Free the key
            @openssl_free_key($privateKey);
        }
    }
}
