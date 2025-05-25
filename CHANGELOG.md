# Changelog

All notable changes to `laravel-netopia-payments` will be documented in this file.

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
