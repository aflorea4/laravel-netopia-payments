<?php

namespace Aflorea4\NetopiaPayments\Helpers;

/**
 * Simple RC4 implementation for Netopia Payments
 * This is a direct implementation that doesn't rely on OpenSSL functions
 */
class SimpleRc4
{
    /**
     * Encrypt or decrypt data using RC4 algorithm
     * RC4 is symmetric, so the same function is used for both encryption and decryption
     *
     * @param string $data The data to encrypt/decrypt
     * @param string $key The encryption/decryption key
     * @return string The encrypted/decrypted data
     */
    public static function crypt($data, $key)
    {
        // Convert key to array of integers
        $keyBytes = [];
        for ($i = 0; $i < strlen($key); $i++) {
            $keyBytes[] = ord($key[$i]);
        }
        
        // Initialize state array
        $state = [];
        for ($i = 0; $i < 256; $i++) {
            $state[$i] = $i;
        }
        
        // Key-scheduling algorithm (KSA)
        $j = 0;
        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $state[$i] + $keyBytes[$i % count($keyBytes)]) % 256;
            
            // Swap values
            $temp = $state[$i];
            $state[$i] = $state[$j];
            $state[$j] = $temp;
        }
        
        // Pseudo-random generation algorithm (PRGA)
        $i = $j = 0;
        $result = '';
        
        for ($k = 0; $k < strlen($data); $k++) {
            $i = ($i + 1) % 256;
            $j = ($j + $state[$i]) % 256;
            
            // Swap values
            $temp = $state[$i];
            $state[$i] = $state[$j];
            $state[$j] = $temp;
            
            // XOR operation
            $result .= chr(ord($data[$k]) ^ $state[($state[$i] + $state[$j]) % 256]);
        }
        
        return $result;
    }
}
