<?php

namespace Aflorea4\NetopiaPayments\Helpers;

/**
 * Custom RC4 implementation for Netopia Payments
 * This is used as a fallback when the built-in RC4 cipher is not available in PHP
 */
class RC4Cipher
{
    /**
     * Encrypt data using RC4 algorithm
     *
     * @param string $data The data to encrypt
     * @param string $key The encryption key
     * @return string The encrypted data
     */
    public static function encrypt($data, $key)
    {
        return self::rc4($data, $key);
    }

    /**
     * Decrypt data using RC4 algorithm
     *
     * @param string $data The data to decrypt
     * @param string $key The decryption key
     * @return string The decrypted data
     */
    public static function decrypt($data, $key)
    {
        // RC4 is symmetric, so encryption and decryption are the same operation
        return self::rc4($data, $key);
    }

    /**
     * RC4 algorithm implementation
     *
     * @param string $data The data to process
     * @param string $key The key
     * @return string The processed data
     */
    private static function rc4($data, $key)
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
