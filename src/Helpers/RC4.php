<?php

namespace Aflorea4\NetopiaPayments\Helpers;

/**
 * Simple RC4 implementation for Netopia Payments
 * This class provides a direct RC4 encryption/decryption method
 */
class RC4
{
    /**
     * RC4 algorithm implementation for encryption/decryption
     * RC4 is symmetric, so the same function is used for both operations
     *
     * @param string $data The data to encrypt/decrypt
     * @param string $key The key to use
     * @return string The encrypted/decrypted data
     */
    public static function crypt($data, $key)
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
}
