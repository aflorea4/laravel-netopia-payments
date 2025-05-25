<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Netopia Payments Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration for the Netopia Payments
    | integration. You can specify your merchant signature, public and private
    | key paths, and whether to use the live or sandbox environment.
    |
    */

    /**
     * The Netopia merchant signature (merchant identifier)
     * This is provided by Netopia when you create a merchant account
     */
    'signature' => env('NETOPIA_SIGNATURE', ''),

    /**
     * The path to the public key file
     * This is provided by Netopia when you create a merchant account
     */
    'public_key_path' => env('NETOPIA_PUBLIC_KEY_PATH', storage_path('app/keys/netopia/public.cer')),

    /**
     * The path to the private key file
     * This is provided by Netopia when you create a merchant account
     */
    'private_key_path' => env('NETOPIA_PRIVATE_KEY_PATH', storage_path('app/keys/netopia/private.key')),

    /**
     * Whether to use the live environment or the sandbox environment
     * Set to true for production, false for testing
     */
    'live_mode' => env('NETOPIA_LIVE_MODE', false),

    /**
     * The currency to use for payments
     * Default is RON (Romanian Leu)
     */
    'default_currency' => env('NETOPIA_DEFAULT_CURRENCY', 'RON'),
];
