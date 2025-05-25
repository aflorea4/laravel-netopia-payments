<?php

namespace Aflorea4\NetopiaPayments\Helpers;

use Exception;

/**
 * Netopia RC4 implementation
 * This class provides RC4 encryption and decryption methods compatible with Netopia Payments
 */
class NetopiaRC4
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
        // Check if RC4 is available
        if (!in_array('rc4', openssl_get_cipher_methods())) {
            throw new Exception('RC4 cipher is not available in this PHP installation');
        }
        
        // Read the public key
        $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
        if ($publicKey === false) {
            throw new Exception('Could not read public key');
        }
        
        try {
            // Generate a random RC4 key
            $rc4Key = openssl_random_pseudo_bytes(16);
            
            // Encrypt the data with RC4
            $encryptedData = openssl_encrypt(
                $data,
                'rc4',
                $rc4Key,
                OPENSSL_RAW_DATA
            );
            
            if ($encryptedData === false) {
                throw new Exception('Failed to encrypt data with RC4');
            }
            
            // Encrypt the RC4 key with the public key
            $encryptedKey = '';
            if (!openssl_public_encrypt($rc4Key, $encryptedKey, $publicKey)) {
                throw new Exception('Could not encrypt RC4 key with public key');
            }
            
            // Return the encrypted data
            return [
                'env_key' => base64_encode($encryptedKey),
                'data' => base64_encode($encryptedData),
                'cipher' => 'rc4',
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
     * @return string The decrypted data
     * @throws Exception
     */
    public static function decrypt($encryptedKey, $encryptedData, $privateKeyPath)
    {
        // Check if RC4 is available
        if (!in_array('rc4', openssl_get_cipher_methods())) {
            throw new Exception('RC4 cipher is not available in this PHP installation');
        }
        
        // Decode the base64 encoded data
        $encryptedKey = base64_decode($encryptedKey);
        $encryptedData = base64_decode($encryptedData);
        
        // Read the private key
        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        if ($privateKey === false) {
            throw new Exception('Could not read private key');
        }
        
        try {
            // Decrypt the RC4 key with the private key
            $rc4Key = '';
            if (!openssl_private_decrypt($encryptedKey, $rc4Key, $privateKey)) {
                throw new Exception('Could not decrypt the envelope key');
            }
            
            // Decrypt the data with RC4
            $decryptedData = openssl_decrypt(
                $encryptedData,
                'rc4',
                $rc4Key,
                OPENSSL_RAW_DATA
            );
            
            if ($decryptedData === false) {
                throw new Exception('Failed to decrypt data with RC4');
            }
            
            return $decryptedData;
        } finally {
            // Free the key
            @openssl_free_key($privateKey);
        }
    }
    
    /**
     * Fallback RC4 implementation for when openssl_encrypt/decrypt is not available with RC4
     * 
     * @param string $data The data to encrypt/decrypt
     * @param string $key The key to use
     * @return string The encrypted/decrypted data
     */
    public static function rc4($data, $key)
    {
        $s = [];
        for ($i = 0; $i < 256; $i++) {
            $s[$i] = $i;
        }
        
        $j = 0;
        $keyLength = strlen($key);
        
        // Key-scheduling algorithm (KSA)
        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $s[$i] + ord($key[$i % $keyLength])) % 256;
            // Swap values
            $temp = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $temp;
        }
        
        $i = $j = 0;
        $result = '';
        $dataLength = strlen($data);
        
        // Pseudo-random generation algorithm (PRGA)
        for ($k = 0; $k < $dataLength; $k++) {
            $i = ($i + 1) % 256;
            $j = ($j + $s[$i]) % 256;
            
            // Swap values
            $temp = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $temp;
            
            // XOR operation
            $result .= chr(ord($data[$k]) ^ $s[($s[$i] + $s[$j]) % 256]);
        }
        
        return $result;
    }
    
    /**
     * Fallback encrypt method using our custom RC4 implementation
     *
     * @param string $data The data to encrypt
     * @param string $publicKeyPath Path to the public key file
     * @return array The encrypted data with envelope key and cipher info
     * @throws Exception
     */
    public static function encryptFallback($data, $publicKeyPath)
    {
        // Read the public key
        $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
        if ($publicKey === false) {
            throw new Exception('Could not read public key');
        }
        
        try {
            // Generate a random RC4 key
            $rc4Key = openssl_random_pseudo_bytes(16);
            
            // Encrypt the data with our custom RC4 implementation
            $encryptedData = self::rc4($data, $rc4Key);
            
            // Encrypt the RC4 key with the public key
            $encryptedKey = '';
            if (!openssl_public_encrypt($rc4Key, $encryptedKey, $publicKey)) {
                throw new Exception('Could not encrypt RC4 key with public key');
            }
            
            // Return the encrypted data
            return [
                'env_key' => base64_encode($encryptedKey),
                'data' => base64_encode($encryptedData),
                'cipher' => 'rc4-fallback',
            ];
        } finally {
            // Free the key
            @openssl_free_key($publicKey);
        }
    }
    
    /**
     * Fallback decrypt method using our custom RC4 implementation
     *
     * @param string $encryptedKey The encrypted RC4 key (base64 encoded)
     * @param string $encryptedData The encrypted data (base64 encoded)
     * @param string $privateKeyPath Path to the private key file
     * @return string The decrypted data
     * @throws Exception
     */
    public static function decryptFallback($encryptedKey, $encryptedData, $privateKeyPath)
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
            // Decrypt the RC4 key with the private key
            $rc4Key = '';
            if (!openssl_private_decrypt($encryptedKey, $rc4Key, $privateKey)) {
                throw new Exception('Could not decrypt the envelope key');
            }
            
            // Decrypt the data with our custom RC4 implementation
            return self::rc4($encryptedData, $rc4Key);
        } finally {
            // Free the key
            @openssl_free_key($privateKey);
        }
    }
}
