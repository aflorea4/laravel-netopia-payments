<?php

namespace Aflorea4\NetopiaPayments\Helpers;

use Felix\RC4\RC4 as FelixRC4;
use Exception;

/**
 * Fixed Key RC4 implementation for Netopia Payments
 * This class provides RC4 encryption and decryption with a fixed key for compatibility with Netopia
 */
class FixedKeyRC4
{
    /**
     * The fixed encryption key to use for RC4
     * This key should be kept secret and should be the same for encryption and decryption
     * 
     * @var string
     */
    private static $fixedKey = 'NetopiaPaymentsRC4Key';
    
    /**
     * Set the fixed encryption key
     * 
     * @param string $key The key to use for encryption and decryption
     */
    public static function setKey($key)
    {
        self::$fixedKey = $key;
    }
    
    /**
     * Encrypt data using RC4 algorithm with the fixed key
     * 
     * @param string $data The data to encrypt
     * @return string The encrypted data (base64 encoded)
     */
    public static function encrypt($data)
    {
        $encrypted = FelixRC4::rc4(self::$fixedKey, $data);
        return base64_encode($encrypted);
    }
    
    /**
     * Decrypt data using RC4 algorithm with the fixed key
     * 
     * @param string $data The data to decrypt (base64 encoded)
     * @return string The decrypted data
     */
    public static function decrypt($data)
    {
        $decoded = base64_decode($data);
        return FelixRC4::rc4(self::$fixedKey, $decoded);
    }
}
