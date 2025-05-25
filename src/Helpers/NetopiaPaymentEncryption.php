<?php

namespace Aflorea4\NetopiaPayments\Helpers;

use Exception;
use Felix\RC4\RC4 as FelixRC4;

/**
 * Netopia Payment Encryption Helper
 * This class provides encryption and decryption methods compatible with Netopia Payments
 */
class NetopiaPaymentEncryption
{
    /**
     * Encrypt data using RC4 algorithm
     *
     * @param string $data The data to encrypt
     * @param string $signature The Netopia merchant signature
     * @param string $publicKeyPath Path to the public key file
     * @return array The encrypted data with envelope key and cipher info
     * @throws Exception
     */
    public static function encrypt($data, $signature, $publicKeyPath)
    {
        // First try built-in RC4 if available
        if (in_array('rc4', openssl_get_cipher_methods())) {
            try {
                // Read the public key
                $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
                if ($publicKey === false) {
                    throw new Exception('Could not read public key');
                }
                
                $sealed = '';
                $envKeys = [];
                
                // Try to encrypt with RC4
                $encryptSuccess = openssl_seal($data, $sealed, $envKeys, [$publicKey], 'RC4');
                
                // Free the key
                @openssl_free_key($publicKey);
                
                if ($encryptSuccess) {
                    return [
                        'env_key' => base64_encode($envKeys[0]),
                        'data' => base64_encode($sealed),
                        'cipher' => 'rc4',
                    ];
                }
            } catch (Exception $e) {
                // If built-in RC4 fails, continue with other methods
            }
        }
        
        // Use Felix RC4 implementation as a fallback
        try {
            // Create a signature-based key for security
            $key = 'Netopia_' . $signature . '_Key';
            
            // Encrypt the data with Felix RC4
            $encryptedData = FelixRC4::rc4($key, $data);
            
            // Return the encrypted data
            return [
                'env_key' => base64_encode($key),  // Store the key for reference
                'data' => base64_encode($encryptedData),
                'cipher' => 'felix-rc4', // Mark as Felix RC4 implementation
            ];
        } catch (Exception $e) {
            throw new Exception('Could not encrypt data: ' . $e->getMessage());
        }
    }
    
    /**
     * Decrypt data using RC4 algorithm
     *
     * @param string $envKey The envelope key (base64 encoded)
     * @param string $data The encrypted data (base64 encoded)
     * @param string $signature The Netopia merchant signature
     * @param string $privateKeyPath Path to the private key file
     * @param string $cipher The cipher used for encryption
     * @return string The decrypted data
     * @throws Exception
     */
    public static function decrypt($envKey, $data, $signature, $privateKeyPath, $cipher = 'rc4')
    {
        // Handle built-in RC4
        if ($cipher === 'rc4' && in_array('rc4', openssl_get_cipher_methods())) {
            try {
                // Decode the base64 encoded data
                $envKey = base64_decode($envKey);
                $data = base64_decode($data);
                
                // Read the private key
                $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
                if ($privateKey === false) {
                    throw new Exception('Could not read private key');
                }
                
                // Decrypt with built-in RC4
                $decryptedData = '';
                if (openssl_open($data, $decryptedData, $envKey, $privateKey, 'rc4')) {
                    // Free the key
                    @openssl_free_key($privateKey);
                    return $decryptedData;
                }
                
                // Free the key
                @openssl_free_key($privateKey);
            } catch (Exception $e) {
                // If built-in RC4 fails, continue with other methods
            }
        }
        
        // Handle Felix RC4
        if ($cipher === 'felix-rc4') {
            try {
                // Decode the base64 encoded data
                $data = base64_decode($data);
                
                // Create a signature-based key for security
                $key = 'Netopia_' . $signature . '_Key';
                
                // Decrypt with Felix RC4
                return FelixRC4::rc4($key, $data);
            } catch (Exception $e) {
                throw new Exception('Could not decrypt data: ' . $e->getMessage());
            }
        }
        
        // If we get here, decryption failed
        throw new Exception('Could not decrypt data with the specified cipher: ' . $cipher);
    }
}
