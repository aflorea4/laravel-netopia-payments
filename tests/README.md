# Netopia Payments Sandbox Test

This directory contains tools to test the Netopia Payments integration with sandbox credentials before releasing updates.

## How to use the sandbox test

1. Place your sandbox certificates in the `certs` directory:
   - `public.cer` - The public certificate provided by Netopia
   - `private.key` - The private key provided by Netopia

2. Edit the `sandbox-test.php` file to add your Netopia signature:
   ```php
   $config = [
       'signature' => 'YOUR_SIGNATURE', // Replace with your actual signature
       'public_key_path' => __DIR__ . '/certs/public.cer',
       'private_key_path' => __DIR__ . '/certs/private.key',
       'live_mode' => false, // Use sandbox mode
       'default_currency' => 'RON',
   ];
   ```

3. Run the test script:
   ```bash
   php tests/sandbox-test.php
   ```

4. The script will attempt to generate payment form data using your sandbox credentials.
   - If successful, it confirms that the encryption is working properly.
   - If it fails, it will display an error message to help diagnose the issue.

## Testing Process

The test script performs the following:

1. Creates a Netopia Payments instance with your sandbox credentials
2. Generates a test payment request with dummy order data
3. Attempts to encrypt the payment data using the current cipher settings
4. Outputs the generated payment form data if successful

This allows you to verify that the encryption/decryption process is working correctly before pushing changes to production.
