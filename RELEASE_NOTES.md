# Release Notes for v0.2.6

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
