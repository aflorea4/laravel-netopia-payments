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
     * Encrypt data using RC4 algorithm with envelope key
     * This method simulates the openssl_seal function but using our custom RC4 implementation
     *
     * @param string $data The data to encrypt
     * @param string &$sealed_data The sealed data (output parameter)
     * @param array &$env_keys The envelope keys (output parameter)
     * @param array $pub_key_ids Array of public key identifiers
     * @return bool True on success, false on failure
     */
    public static function seal($data, &$sealed_data, &$env_keys, $pub_key_ids)
    {
        try {
            // Generate a random key for RC4
            $rc4_key = openssl_random_pseudo_bytes(16);
            
            // Encrypt the data with RC4
            $sealed_data = self::rc4($data, $rc4_key);
            
            // Encrypt the RC4 key with each public key
            $env_keys = [];
            foreach ($pub_key_ids as $key_id) {
                $encrypted_key = '';
                if (!openssl_public_encrypt($rc4_key, $encrypted_key, $key_id)) {
                    return false;
                }
                $env_keys[] = $encrypted_key;
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Decrypt data using RC4 algorithm with envelope key
     * This method simulates the openssl_open function but using our custom RC4 implementation
     *
     * @param string $sealed_data The sealed data
     * @param string &$open_data The opened data (output parameter)
     * @param string $env_key The envelope key
     * @param mixed $priv_key_id The private key identifier
     * @return bool True on success, false on failure
     */
    public static function open($sealed_data, &$open_data, $env_key, $priv_key_id)
    {
        try {
            // Decrypt the RC4 key with the private key
            $rc4_key = '';
            if (!openssl_private_decrypt($env_key, $rc4_key, $priv_key_id)) {
                return false;
            }
            
            // Decrypt the data with RC4
            $open_data = self::rc4($sealed_data, $rc4_key);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
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
