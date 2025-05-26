<?php

namespace Aflorea4\NetopiaPayments\Helpers;

use Exception;

/**
 * Netopia Payment Encryption Helper
 * This class provides encryption and decryption methods compatible with Netopia Payments
 * Only supports AES-256-CBC encryption for PHP 7+
 */
class NetopiaPaymentEncryption
{
    // Error constants
    const ERROR_REQUIRED_CIPHER_NOT_AVAILABLE = 1;
    
    /**
     * Encrypt data using AES-256-CBC
     *
     * @param string $data The data to encrypt
     * @param string $signature The Netopia merchant signature
     * @param string $publicKeyPath Path to the public key file
     * @return array The encrypted data with envelope key and cipher info
     * @throws Exception
     */
    public static function encrypt($data, $signature, $publicKeyPath)
    {
        // Always use AES-256-CBC as we only support PHP 7+
        $cipher = 'aes-256-cbc';
        
        // Verify that AES-256-CBC is available
        if (!in_array('aes-256-cbc', openssl_get_cipher_methods())) {
            throw new Exception('AES-256-CBC cipher is not available in this PHP installation', self::ERROR_REQUIRED_CIPHER_NOT_AVAILABLE);
        }
        
        try {
            // Read the public key
            $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
            if ($publicKey === false) {
                throw new Exception('Could not read public key');
            }
            
            // Generate a random IV
            $iv = openssl_random_pseudo_bytes(16);
            
            // Generate a random key for AES
            $aesKey = openssl_random_pseudo_bytes(32); // 256 bits
            
            // Encrypt data with AES
            $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);
            if ($encryptedData === false) {
                throw new Exception('AES encryption failed: ' . openssl_error_string());
            }
            
            // Encrypt the AES key with the public key
            $encryptedKey = '';
            if (!openssl_public_encrypt($aesKey, $encryptedKey, $publicKey, OPENSSL_PKCS1_PADDING)) {
                throw new Exception('Failed to encrypt AES key: ' . openssl_error_string());
            }
            
            // Free the key
            @openssl_free_key($publicKey);
            
            return [
                'env_key' => base64_encode($encryptedKey),
                'data' => base64_encode($encryptedData),
                'cipher' => 'aes-256-cbc',
                'iv' => base64_encode($iv)
            ];
        } catch (Exception $e) {
            throw new Exception('AES encryption failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Decrypt data using AES-256-CBC
     *
     * @param string $envKey The envelope key (base64 encoded)
     * @param string $data The encrypted data (base64 encoded)
     * @param string $signature The Netopia merchant signature
     * @param string $privateKeyPath Path to the private key file
     * @param string $cipher The cipher used for encryption (should always be aes-256-cbc)
     * @param string|null $iv The initialization vector for AES (base64 encoded)
     * @return string The decrypted data
     * @throws Exception
     */
    public static function decrypt($envKey, $data, $signature, $privateKeyPath, $cipher = 'aes-256-cbc', $iv = null)
    {
        // Verify that AES-256-CBC is available
        if (!in_array('aes-256-cbc', openssl_get_cipher_methods())) {
            throw new Exception('AES-256-CBC cipher is not available in this PHP installation');
        }
        
        // Only support AES-256-CBC
        if ($cipher !== 'aes-256-cbc') {
            throw new Exception('Unsupported cipher: ' . $cipher . '. Only AES-256-CBC is supported.');
        }
        
        try {
            // Decode the base64 encoded data
            $encryptedKey = base64_decode($envKey);
            $encryptedData = base64_decode($data);
            $iv = base64_decode($iv);
            
            if (empty($iv) || strlen($iv) !== 16) {
                throw new Exception('Invalid initialization vector for AES-256-CBC');
            }
            
            // Read the private key
            $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
            if ($privateKey === false) {
                throw new Exception('Could not read private key');
            }
            
            // Decrypt the AES key with the private key
            $aesKey = '';
            if (!openssl_private_decrypt($encryptedKey, $aesKey, $privateKey, OPENSSL_PKCS1_PADDING)) {
                throw new Exception('Failed to decrypt AES key: ' . openssl_error_string());
            }
            
            // Free the key
            @openssl_free_key($privateKey);
            
            // Decrypt the data with AES
            $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);
            if ($decryptedData === false) {
                throw new Exception('AES decryption failed: ' . openssl_error_string());
            }
            
            return $decryptedData;
        } catch (Exception $e) {
            throw new Exception('AES decryption failed: ' . $e->getMessage());
        }
    }
}
