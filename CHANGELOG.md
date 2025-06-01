# Changelog

All notable changes to `laravel-netopia-payments` will be documented in this file.

## 0.2.6 - 2025-06-01

- **SECURITY ENHANCEMENT**: Package now exclusively uses AES-256-CBC encryption/decryption
- Removed support for deprecated RC4 cipher
- Made initialization vector (IV) parameter required for all decryption operations
- Updated controllers to validate the IV parameter
- Simplified encryption/decryption API

## 0.0.5 - 2025-05-25

- **CRITICAL FIX**: Added support for multiple cipher algorithms to handle RC4 deprecation in newer PHP versions
- Updated encryption and decryption methods to automatically fall back to supported ciphers (AES-128-CBC, AES-256-CBC, BF-CBC)
- Enhanced error handling with more descriptive error messages

## 0.0.4 - 2025-05-25

- **CRITICAL FIX**: Changed cipher algorithm from AES256 to RC4 to match Netopia's official implementation
- Removed IV parameter which is not required for RC4 cipher
- Updated all form templates and controllers to use the correct cipher parameter

## 0.0.3 - 2025-05-25

- Added parameter validation to ensure all required parameters (env_key, data, iv) are present
- Enhanced error logging with detailed context for easier debugging
- Added test coverage for the return method in the payment controller
- Updated documentation to clearly state that all three parameters are required
- Fixed missing IV parameter in payment form templates

## 0.0.2 - 2025-05-25

- Fixed bug with AES256 encryption requiring an initialization vector (IV)
- Updated controller to handle the IV parameter in payment processing

## 0.0.1 - 2025-05-25

- Initial release
- Support for Netopia Payments integration with Laravel
- Payment request generation
- Payment response processing
- Event-based payment notification handling
- Test transaction support
