# Upgrade Guide

This document provides instructions for upgrading between major versions of the Laravel Netopia Payments package.

## Upgrading from 0.2.5 to 0.2.6

Version 0.2.6 introduces a significant security enhancement by exclusively using AES-256-CBC encryption/decryption and removing support for the deprecated RC4 cipher. This change requires a few updates to your implementation:

### Required Changes

1. **Initialization Vector (IV) is now required**:
   - The IV parameter is now mandatory for all decryption operations
   - Make sure your payment processing code always passes the IV parameter

2. **Controller Updates**:
   - If you've customized the payment controller, update your `processResponse` method calls:

   ```php
   // Before (0.2.5)
   $response = NetopiaPayments::processResponse(
       $request->input('env_key'),
       $request->input('data'),
       $request->input('cipher', 'RC4'),
       null,
       $request->input('iv')
   );

   // After (0.2.6)
   $response = NetopiaPayments::processResponse(
       $request->input('env_key'),
       $request->input('data'),
       null,
       $request->input('iv')
   );
   ```

3. **Parameter Validation**:
   - Update your validation to ensure the IV parameter is present:

   ```php
   // Before (0.2.5)
   if (empty($envKey) || empty($data)) {
       // Error handling
   }

   // After (0.2.6)
   if (empty($envKey) || empty($data) || empty($iv)) {
       // Error handling
   }
   ```

### Benefits of This Update

- **Improved Security**: AES-256-CBC provides stronger encryption compared to RC4
- **Future Compatibility**: RC4 is considered insecure and is deprecated in many environments
- **Simplified API**: The encryption/decryption API is now more straightforward

### Testing After Upgrade

After upgrading, test your payment flow in the sandbox environment to ensure everything works correctly with the new encryption method.
