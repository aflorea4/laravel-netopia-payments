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
            $publicKeyContent = file_get_contents($publicKeyPath);
            if ($publicKeyContent === false) {
                throw new Exception('Could not read public key file: ' . $publicKeyPath);
            }
            
            $publicKey = openssl_pkey_get_public($publicKeyContent);
            if ($publicKey === false) {
                throw new Exception('Could not load public key: ' . openssl_error_string());
            }
            
            // Initialize IV as null - openssl_seal will generate it
            $iv = null;
            
            // Prepare the public keys array for openssl_seal
            $publicKeys = [$publicKey];
            
            // Use openssl_seal for encryption (similar to Netopia's implementation)
            // This will encrypt the data and automatically generate an IV
            $encryptedData = null;
            $envKeys = null;
            $result = openssl_seal($data, $encryptedData, $envKeys, $publicKeys, $cipher, $iv);
            
            if ($result === false) {
                $errorMessage = "Error while encrypting data! Reason:";
                while (($errorString = openssl_error_string())) {
                    $errorMessage .= $errorString . "\n";
                }
                throw new Exception($errorMessage);
            }
            
            // Free the key
            @openssl_free_key($publicKey);
            
            // Base64 encode all binary data for safe transmission
            $base64EnvKey = base64_encode($envKeys[0]);
            $base64Data = base64_encode($encryptedData);
            $base64Iv = base64_encode($iv); // IV is now populated by openssl_seal
            
            return [
                'env_key' => $base64EnvKey,
                'data' => $base64Data,
                'cipher' => 'aes-256-cbc',
                'iv' => $base64Iv
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
     * @param string $cipher The cipher used for encryption (always aes-256-cbc)
     * @param string $iv The initialization vector for AES (base64 encoded)
     * @return string The decrypted data
     * @throws Exception
     */
    public static function decrypt($envKey, $data, $signature, $privateKeyPath, $cipher = 'aes-256-cbc', $iv)
    {
        // Verify that AES-256-CBC is available
        if (!in_array('aes-256-cbc', openssl_get_cipher_methods())) {
            throw new Exception('AES-256-CBC cipher is not available in this PHP installation');
        }
        
        try {
            // Decode the base64 encoded data
            $encryptedKey = base64_decode($envKey);
            if ($encryptedKey === false) {
                throw new Exception('Invalid base64 encoding for envelope key');
            }
            
            $encryptedData = base64_decode($data);
            if ($encryptedData === false) {
                throw new Exception('Invalid base64 encoding for encrypted data');
            }
            
            // Handle IV - ensure it's properly decoded and has correct length
            if (empty($iv)) {
                throw new Exception('Initialization vector (IV) is required for AES-256-CBC');
            }
            
            $iv = base64_decode($iv);
            if ($iv === false) {
                throw new Exception('Invalid base64 encoding for initialization vector');
            }
            
            if (strlen($iv) !== 16) {
                throw new Exception('Invalid initialization vector length: ' . strlen($iv) . ' bytes. Expected 16 bytes for AES-256-CBC.');
            }
            
            // Read the private key
            $privateKeyContent = file_get_contents($privateKeyPath);
            if ($privateKeyContent === false) {
                throw new Exception('Could not read private key file: ' . $privateKeyPath);
            }
            
            $privateKey = openssl_pkey_get_private($privateKeyContent);
            if ($privateKey === false) {
                throw new Exception('Could not load private key: ' . openssl_error_string());
            }
            
            // Decrypt the AES key with the private key
            $aesKey = '';
            if (!openssl_private_decrypt($encryptedKey, $aesKey, $privateKey, OPENSSL_PKCS1_PADDING)) {
                throw new Exception('Failed to decrypt AES key: ' . openssl_error_string());
            }
            
            // Note: We're not strictly validating the key length here as it may vary across environments
            // The openssl_decrypt function will handle the key appropriately
            
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
