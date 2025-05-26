<?php

namespace Aflorea4\NetopiaPayments\Helpers;

use Exception;

/**
 * Fixed Key Encryption implementation for Netopia Payments
 * This class provides AES-256-CBC encryption and decryption with a fixed key
 * RC4 support has been removed in favor of more secure AES-256-CBC
 */
class FixedKeyRC4
{
    /**
     * The fixed encryption key to use for AES
     * This key should be kept secret and should be the same for encryption and decryption
     * 
     * @var string
     */
    private static $fixedKey = 'NetopiaPaymentsAESKey123456789012345678901234';
    
    /**
     * Set the fixed encryption key
     * Note: For AES-256-CBC, the key should be 32 bytes long
     * 
     * @param string $key The key to use for encryption and decryption
     */
    public static function setKey($key)
    {
        // Ensure the key is exactly 32 bytes (256 bits) for AES-256-CBC
        self::$fixedKey = substr(str_pad($key, 32, '0'), 0, 32);
    }
    
    /**
     * Encrypt data using AES-256-CBC algorithm with the fixed key
     * 
     * @param string $data The data to encrypt
     * @return string The encrypted data (base64 encoded)
     */
    public static function encrypt($data)
    {
        // Generate a random IV
        $iv = openssl_random_pseudo_bytes(16);
        
        // Encrypt the data
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', self::$fixedKey, OPENSSL_RAW_DATA, $iv);
        
        if ($encrypted === false) {
            throw new Exception('AES encryption failed: ' . openssl_error_string());
        }
        
        // Prepend the IV to the encrypted data and base64 encode
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data using AES-256-CBC algorithm with the fixed key
     * 
     * @param string $data The data to decrypt (base64 encoded)
     * @return string The decrypted data
     */
    public static function decrypt($data)
    {
        $decoded = base64_decode($data);
        
        // Extract the IV (first 16 bytes)
        $iv = substr($decoded, 0, 16);
        $ciphertext = substr($decoded, 16);
        
        // Decrypt the data
        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', self::$fixedKey, OPENSSL_RAW_DATA, $iv);
        
        if ($decrypted === false) {
            throw new Exception('AES decryption failed: ' . openssl_error_string());
        }
        
        return $decrypted;
    }
}
