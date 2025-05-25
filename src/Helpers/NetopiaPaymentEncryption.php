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
    // Error constants
    const ERROR_REQUIRED_CIPHER_NOT_AVAILABLE = 1;
    
    /**
     * Encrypt data using appropriate algorithm based on PHP version
     *
     * @param string $data The data to encrypt
     * @param string $signature The Netopia merchant signature
     * @param string $publicKeyPath Path to the public key file
     * @return array The encrypted data with envelope key and cipher info
     * @throws Exception
     */
    public static function encrypt($data, $signature, $publicKeyPath)
    {
        // Determine which cipher to use based on PHP version
        $cipher = 'rc4'; // Default cipher
        
        // For PHP 7.0+ use AES-256-CBC if OpenSSL version is high enough
        if (PHP_VERSION_ID >= 70000) {
            if (OPENSSL_VERSION_NUMBER > 0x10000000) {
                $cipher = 'aes-256-cbc';
            }
        } else {
            // For older PHP versions with newer OpenSSL, throw an error
            if (OPENSSL_VERSION_NUMBER >= 0x30000000) {
                $errorMessage = 'Incompatible configuration PHP ' . PHP_VERSION . ' & ' . OPENSSL_VERSION_TEXT;
                throw new Exception($errorMessage, self::ERROR_REQUIRED_CIPHER_NOT_AVAILABLE);
            }
        }
        
        // Use AES-256-CBC for PHP 7.0+
        if ($cipher === 'aes-256-cbc' && in_array('aes-256-cbc', openssl_get_cipher_methods())) {
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
                // If AES fails, continue with RC4 as fallback
            }
        }
        
        // Try built-in RC4 if available
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
        
        // Use Felix RC4 implementation as a last fallback
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
     * Decrypt data using the appropriate algorithm
     *
     * @param string $envKey The envelope key (base64 encoded)
     * @param string $data The encrypted data (base64 encoded)
     * @param string $signature The Netopia merchant signature
     * @param string $privateKeyPath Path to the private key file
     * @param string $cipher The cipher used for encryption
     * @param string|null $iv The initialization vector for AES (base64 encoded)
     * @return string The decrypted data
     * @throws Exception
     */
    public static function decrypt($envKey, $data, $signature, $privateKeyPath, $cipher = 'rc4', $iv = null)
    {
        // Handle AES-256-CBC
        if ($cipher === 'aes-256-cbc' && in_array('aes-256-cbc', openssl_get_cipher_methods())) {
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
                // If AES decryption fails, continue with other methods
            }
        }
        
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
