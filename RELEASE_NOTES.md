# Release Notes

This document contains the release notes for the Laravel Netopia Payments package.

## Version 0.2.8 (2025-06-01)

### Encryption Implementation Enhancement

This release improves the encryption implementation to better align with Netopia's official approach:

- Updated the encryption method to use `openssl_seal()` for both symmetric and asymmetric encryption
- Let OpenSSL automatically generate the initialization vector (IV) during encryption
- Maintained exclusive use of AES-256-CBC for all encryption operations
- Ensured IV is always included in payment form data

These changes improve compatibility with Netopia's payment processing system while maintaining the security benefits of using AES-256-CBC encryption.

## Security Enhancement: AES-256-CBC Encryption

This release focuses on enhancing the security of the Laravel Netopia Payments package by enforcing the use of AES-256-CBC encryption and removing support for the deprecated RC4 cipher.

### Key Changes

- **SECURITY ENHANCEMENT**: Package now exclusively uses AES-256-CBC encryption/decryption
- Removed support for deprecated RC4 cipher
- Made initialization vector (IV) parameter required for all decryption operations
- Updated controllers to validate the IV parameter
- Simplified encryption/decryption API
- Added comprehensive upgrade guide

### Upgrade Instructions

Please refer to the [UPGRADE.md](UPGRADE.md) file for detailed instructions on how to update your implementation to work with this new version.

### Benefits

- **Improved Security**: AES-256-CBC provides stronger encryption compared to RC4
- **Future Compatibility**: RC4 is considered insecure and is deprecated in many environments
- **Simplified API**: The encryption/decryption API is now more straightforward

### Testing

After upgrading, we strongly recommend testing your payment flow in the sandbox environment to ensure everything works correctly with the new encryption method.
