# Laravel Netopia Payments

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aflorea4/laravel-netopia-payments.svg?style=flat-square)](https://packagist.org/packages/aflorea4/laravel-netopia-payments)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/aflorea4/laravel-netopia-payments/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/aflorea4/laravel-netopia-payments/actions?query=workflow%3Atests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/aflorea4/laravel-netopia-payments.svg?style=flat-square)](https://packagist.org/packages/aflorea4/laravel-netopia-payments)
[![GitHub License](https://img.shields.io/github/license/aflorea4/laravel-netopia-payments?style=flat-square)](https://github.com/aflorea4/laravel-netopia-payments/blob/main/LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/php-v/aflorea4/laravel-netopia-payments?style=flat-square)](https://packagist.org/packages/aflorea4/laravel-netopia-payments)
[![GitHub Release Date](https://img.shields.io/github/release-date/aflorea4/laravel-netopia-payments?style=flat-square)](https://github.com/aflorea4/laravel-netopia-payments/releases)

A Laravel package for integrating with Netopia Payments (Romania) payment gateway.

## Requirements

- PHP 7.4 or higher
- Laravel 8.0 or higher
- OpenSSL extension
- DOM extension

## Installation

You can install the package via composer:

```bash
composer require aflorea4/laravel-netopia-payments
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Aflorea4\NetopiaPayments\NetopiaPaymentsServiceProvider" --tag="config"
```

This will create a `config/netopia.php` file in your app that you can modify to set your configuration.

### Configuration Options

```php
// config/netopia.php
return [
    // The Netopia merchant signature (merchant identifier)
    'signature' => env('NETOPIA_SIGNATURE', ''),

    // The path to the public key file
    'public_key_path' => env('NETOPIA_PUBLIC_KEY_PATH', storage_path('app/keys/netopia/public.cer')),

    // The path to the private key file
    'private_key_path' => env('NETOPIA_PRIVATE_KEY_PATH', storage_path('app/keys/netopia/private.key')),

    // Whether to use the live environment or the sandbox environment
    'live_mode' => env('NETOPIA_LIVE_MODE', false),

    // The currency to use for payments
    'default_currency' => env('NETOPIA_DEFAULT_CURRENCY', 'RON'),
];
```

Add the following variables to your `.env` file:

```
NETOPIA_SIGNATURE=your-netopia-signature
NETOPIA_PUBLIC_KEY_PATH=/path/to/your/public.cer
NETOPIA_PRIVATE_KEY_PATH=/path/to/your/private.key
NETOPIA_LIVE_MODE=false
NETOPIA_DEFAULT_CURRENCY=RON
```

## Verifying Your Certificate and Private Key

Before using the package in production, it's important to verify that your certificate and private key are valid and working correctly. You can use the following commands to check them:

### Verify the Public Certificate

```bash
openssl x509 -in /path/to/your/public.cer -text -noout
```

This command should display information about your certificate, including the issuer, validity period, and public key details. If the command returns an error, your certificate may be invalid or corrupted.

### Verify the Private Key

```bash
openssl rsa -in /path/to/your/private.key -check
```

This command should display "RSA key ok" if your private key is valid. If it asks for a password, your key may be password-protected, which is not supported by this package.

### Test Key Pair Matching

To verify that your public certificate and private key are a matching pair (which is essential for encryption/decryption to work):

```bash
# Extract the modulus from the certificate
openssl x509 -in /path/to/your/public.cer -noout -modulus | md5sum

# Extract the modulus from the private key
openssl rsa -in /path/to/your/private.key -noout -modulus | md5sum
```

Both commands should produce the same MD5 hash. If they don't match, your certificate and private key are not a valid pair, which will cause encryption/decryption failures.

## Usage

### Creating a Payment Request

```php
use Aflorea4\NetopiaPayments\Facades\NetopiaPayments;

// Create a payment request
$paymentData = NetopiaPayments::createPaymentRequest(
    'ORDER123', // Order ID
    100.00, // Amount
    'RON', // Currency
    route('payment.return'), // Return URL
    route('netopia.confirm'), // Confirm URL
    [
        'type' => 'person', // 'person' or 'company'
        'firstName' => 'John',
        'lastName' => 'Doe',
        'email' => 'john.doe@example.com',
        'address' => '123 Main St',
        'mobilePhone' => '0712345678',
    ],
    'Payment for Order #123' // Description
);

// The payment data contains:
// - env_key: The encrypted envelope key (REQUIRED)
// - data: The encrypted payment data (REQUIRED)
// - cipher: The cipher algorithm used for encryption (REQUIRED, defaults to 'aes-256-cbc')
// - iv: The initialization vector for AES encryption (REQUIRED when using aes-256-cbc)
// - url: The payment URL (sandbox or live)
//
// IMPORTANT: All parameters (env_key, data, cipher, and iv) must be included in your payment form
// submission to Netopia. The cipher parameter should be set to 'aes-256-cbc' for modern implementations.

// Redirect to the payment page
return view('payment.redirect', [
    'paymentData' => $paymentData,
]);
```

### Payment Redirect View

Create a view file `resources/views/payment/redirect.blade.php`:

```html
<!DOCTYPE html>
<html>
  <head>
    <title>Redirecting to payment...</title>
  </head>
  <body>
    <h1>Redirecting to payment...</h1>
    <p>Please wait while we redirect you to the payment page.</p>

    <form id="netopiaForm" action="{{ $paymentData['url'] }}" method="post">
      <input
        type="hidden"
        name="env_key"
        value="{{ $paymentData['env_key'] }}"
      />
      <input type="hidden" name="data" value="{{ $paymentData['data'] }}" />
      <input type="hidden" name="cipher" value="{{ $paymentData['cipher'] ?? 'aes-256-cbc' }}" />
      <input type="hidden" name="iv" value="{{ $paymentData['iv'] ?? '' }}" />
      <button type="submit" style="display: none;">Pay Now</button>
    </form>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("netopiaForm").submit();
      });
    </script>
  </body>
</html>
```

### Handling Payment Notifications

The package automatically registers routes for handling payment notifications:

- `POST /netopia/confirm` - For Instant Payment Notifications (IPN)
- `GET /netopia/return` - For redirecting the user after payment

These routes are handled by the package's internal controller and will dispatch events that you can listen for in your application. You don't need to create these routes yourself.

You can listen for the following events to handle payment notifications:

```php
use Aflorea4\NetopiaPayments\Events\NetopiaPaymentConfirmed;
use Aflorea4\NetopiaPayments\Events\NetopiaPaymentPending;
use Aflorea4\NetopiaPayments\Events\NetopiaPaymentCanceled;

// In your EventServiceProvider.php
protected $listen = [
    NetopiaPaymentConfirmed::class => [
        // Your listeners here
    ],
    NetopiaPaymentPending::class => [
        // Your listeners here
    ],
    NetopiaPaymentCanceled::class => [
        // Your listeners here
    ],
];
```

### Creating a Payment Listener

```php
namespace App\Listeners;

use Aflorea4\NetopiaPayments\Events\NetopiaPaymentConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Order;

class HandleConfirmedPayment implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  NetopiaPaymentConfirmed  $event
     * @return void
     */
    public function handle(NetopiaPaymentConfirmed $event)
    {
        // Get the payment response
        $response = $event->response;

        // Find the order
        $order = Order::where('order_id', $response->orderId)->first();

        if ($order) {
            // Update the order status
            $order->status = 'paid';
            $order->payment_amount = $response->processedAmount;
            $order->save();

            // Additional logic...
        }
    }
}
```

## Testing with a Test Transaction

Here's a complete example of how to handle a test transaction of 1.00 RON without using queues or listeners:

### 1. Create a Controller to Handle the Payment

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aflorea4\NetopiaPayments\Facades\NetopiaPayments;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Initiate a test payment
     */
    public function initiatePayment()
    {
        // Create a payment request for 1.00 RON
        $paymentData = NetopiaPayments::createPaymentRequest(
            'TEST'.time(), // Order ID with timestamp to make it unique
            1.00, // Amount (1.00 RON for testing)
            'RON', // Currency
            route('payment.return'), // Return URL
            route('payment.confirm'), // Confirm URL
            [
                'type' => 'person',
                'firstName' => 'Test',
                'lastName' => 'User',
                'email' => 'test@example.com',
                'address' => 'Test Address',
                'mobilePhone' => '0700000000',
            ],
            'Test payment of 1.00 RON' // Description
        );

        // Redirect to the payment page
        return view('payment.redirect', [
            'paymentData' => $paymentData,
        ]);
    }

    /**
     * Handle the payment confirmation (IPN)
     */
    public function confirmPayment(Request $request)
    {
        try {
            // Process the payment response
            $response = NetopiaPayments::processResponse(
                $request->input('env_key'),
                $request->input('data'),
                null,
                $request->input('iv')
            );

            // Log the payment response
            Log::info('Payment confirmation received', [
                'orderId' => $response->orderId,
                'action' => $response->action,
                'errorCode' => $response->errorCode ?? null,
                'errorMessage' => $response->errorMessage ?? null,
                'processedAmount' => $response->processedAmount ?? null,
            ]);

            // Handle the payment based on the action
            if ($response->isConfirmed()) {
                // Payment was confirmed/approved
                // Update your order status in the database
                // For example:
                // Order::where('order_id', $response->orderId)->update(['status' => 'paid']);

                // Your business logic here...
            } elseif ($response->isPending()) {
                // Payment is pending
                // Update your order status in the database
                // For example:
                // Order::where('order_id', $response->orderId)->update(['status' => 'pending']);
            } elseif ($response->isCanceled()) {
                // Payment was canceled or failed
                // Update your order status in the database
                // For example:
                // Order::where('order_id', $response->orderId)->update(['status' => 'canceled']);
            }

            // Return the appropriate response to Netopia
            return response(
                NetopiaPayments::generatePaymentResponse(),
                200,
                ['Content-Type' => 'application/xml']
            );
        } catch (\Exception $e) {
            // Log the error
            Log::error('Payment confirmation error', [
                'message' => $e->getMessage(),
            ]);

            // Return an error response to Netopia
            return response(
                NetopiaPayments::generatePaymentResponse(1, 1, $e->getMessage()),
                200,
                ['Content-Type' => 'application/xml']
            );
        }
    }

    /**
     * Handle the return from payment page
     */
    public function returnFromPayment(Request $request)
    {
        // This is where the user is redirected after the payment
        // You can show a thank you page or order summary

        // Check if all required payment data is in the request
        if ($request->has('env_key') && $request->has('data') && $request->has('iv')) {
            try {
                // Process the payment response
                $response = NetopiaPayments::processResponse(
                    $request->input('env_key'),
                    $request->input('data')
                );

                // Show different messages based on the payment status
                if ($response->isConfirmed()) {
                    return view('payment.success', ['orderId' => $response->orderId]);
                } elseif ($response->isPending()) {
                    return view('payment.pending', ['orderId' => $response->orderId]);
                } else {
                    return view('payment.failed', ['orderId' => $response->orderId]);
                }
            } catch (\Exception $e) {
                // Log the error
                Log::error('Payment return error', [
                    'message' => $e->getMessage(),
                ]);

                return view('payment.error', ['message' => $e->getMessage()]);
            }
        }

        // Fallback if no payment data is present
        return view('payment.complete');
    }
}
```

### 2. Register the Routes

```php
// routes/web.php
Route::get('/payment/initiate', [PaymentController::class, 'initiatePayment'])->name('payment.initiate');
```

**Note about routes:**
The example below registers custom routes for payment confirmation and return:

```php
// Custom routes if you want to handle payment processing in your own controller
Route::post('/payment/confirm', [PaymentController::class, 'confirmPayment'])->name('payment.confirm');
Route::get('/payment/return', [PaymentController::class, 'returnFromPayment'])->name('payment.return');
```

These custom routes are different from the auto-registered package routes (`/netopia/confirm` and `/netopia/return`). Use custom routes when:
1. You want complete control over the payment flow
2. You need custom logic that isn't covered by the event listeners
3. You're not using the package's event system

If you're using the package's event system, you can use the auto-registered routes instead.

### 3. Create the Necessary Views

Create the redirect view as shown in the main usage example, and create success, pending, failed, and error views as needed.

### 4. Test Card Details

When testing in the sandbox environment, you can use the following test card details to simulate a successful transaction:

```
Card Number: 4111 1111 1111 1111
Expiry Date: Any future date (e.g., 12/25)
CVV: Any 3 digits (e.g., 123)
Cardholder Name: Any name
3D Secure Password: 123456
```

### 5. Testing Process

1. Make sure your `.env` file has `NETOPIA_LIVE_MODE=false` to use the sandbox environment
2. Visit `/payment/initiate` to start a test payment of 1.00 RON
3. You'll be redirected to the Netopia sandbox payment page
4. Enter the test card details provided above
5. Complete the 3D Secure verification with password `123456`
6. The payment will be processed, and you'll be redirected back to your return URL
7. The confirmation endpoint will also receive the payment notification

This approach doesn't use queues or event listeners, making it simpler for testing and development. All payment processing happens synchronously in the controller methods.

## Security

The package uses the following security measures:

1. Request authentication using an API Signature included in the request
2. Data encryption using RSA keys with AES-256-CBC for symmetric encryption
3. Secure Sockets Layer (SSL) data transport

### Encryption Details

As of version 0.2.6, this package exclusively uses AES-256-CBC encryption for all payment data. This provides stronger security compared to older cipher methods like RC4. When processing payments, the initialization vector (IV) parameter is now required for all decryption operations.

## Testing

This package uses PEST for testing. To run the tests, you can use the following command:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Alexandru Florea](https://github.com/aflorea4)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

The test suite includes:

- Unit tests for the NetopiaPayments class
- Feature tests for payment request generation
- Feature tests for payment response processing
- Integration tests for the controller

### Writing Tests

If you want to add more tests, you can create them in the `tests` directory. The package uses PEST's expressive syntax for writing tests. Here's an example of how to write a test for the NetopiaPayments class:

```php
it('can create a payment request', function () {
    $netopiaPayments = new \Aflorea4\NetopiaPayments\NetopiaPayments();

    $paymentData = $netopiaPayments->createPaymentRequest(
        'ORDER123',
        100.00,
        'RON',
        'https://example.com/return',
        'https://example.com/confirm',
        [
            'type' => 'person',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'address' => '123 Main St',
            'mobilePhone' => '0712345678',
        ],
        'Payment for Order #123'
    );

    expect($paymentData)->toBeArray()
        ->toHaveKeys(['env_key', 'data', 'url']);
});
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
