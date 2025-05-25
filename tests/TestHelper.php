<?php

namespace Tests;

class TestHelper
{
    /**
     * Get the Netopia signature from environment or use a test value
     *
     * @return string
     */
    public static function getTestSignature()
    {
        return env('NETOPIA_TEST_SIGNATURE', 'TEST-SIGNATURE-FOR-UNIT-TESTS');
    }
    
    /**
     * Get the path to the test public certificate
     *
     * @return string
     */
    public static function getTestPublicKeyPath()
    {
        return __DIR__ . '/certs/public.cer';
    }
    
    /**
     * Get the path to the test private key
     *
     * @return string
     */
    public static function getTestPrivateKeyPath()
    {
        return __DIR__ . '/certs/private.key';
    }
}
