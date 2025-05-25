<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Aflorea4\NetopiaPayments\NetopiaPayments;

// Configuration
$config = [
    'signature' => 'NETOPIA', // Sandbox signature
    'public_key_path' => __DIR__ . '/certs/public.cer', 
    'private_key_path' => __DIR__ . '/certs/private.key',
    'live_mode' => false, // Use sandbox mode
    'default_currency' => 'RON',
];

// Create directory for certificates if it doesn't exist
if (!file_exists(__DIR__ . '/certs')) {
    mkdir(__DIR__ . '/certs', 0755, true);
}

// Function to save the certificate files
function saveCertificates($publicCert, $privateKey) {
    file_put_contents(__DIR__ . '/certs/public.cer', $publicCert);
    file_put_contents(__DIR__ . '/certs/private.key', $privateKey);
    echo "Certificates saved successfully.\n";
}

// Function to test payment creation
function testPaymentCreation($netopia) {
    try {
        $orderId = 'TEST' . time();
        $amount = 1.00;
        $currency = 'RON';
        $returnUrl = 'http://localhost/return';
        $confirmUrl = 'http://localhost/confirm';
        
        $billingDetails = [
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'test@example.com',
            'phone' => '0700000000',
            'address' => 'Test Address',
            'city' => 'Bucharest',
            'country' => 'Romania',
            'postalCode' => '123456',
        ];
        
        $paymentData = $netopia->generatePaymentFormData(
            $netopia->createPaymentRequest(
                $orderId, 
                $amount, 
                $currency, 
                $returnUrl, 
                $confirmUrl, 
                $billingDetails, 
                'Test payment'
            )
        );
        
        echo "Payment form data generated successfully:\n";
        echo "URL: " . $paymentData['url'] . "\n";
        echo "ENV_KEY: " . $paymentData['env_key'] . "\n";
        echo "DATA: " . $paymentData['data'] . "\n";
        echo "CIPHER: " . $paymentData['cipher'] . "\n";
        if (isset($paymentData['iv'])) {
            echo "IV: " . $paymentData['iv'] . "\n";
        }
        
        return true;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Main execution
echo "Netopia Payments Sandbox Test\n";
echo "----------------------------\n\n";

// Instructions for the user
echo "Please provide the signature, public certificate, and private key for the sandbox environment.\n";
echo "You can edit this file to add your credentials or provide them when prompted.\n\n";

// Check if credentials are already set
if ($config['signature'] === 'YOUR_SIGNATURE') {
    echo "Please update the script with your sandbox credentials before running the test.\n";
    exit(1);
}

// Create Netopia Payments instance
$netopia = new NetopiaPayments(
    $config['signature'],
    $config['public_key_path'],
    $config['private_key_path'],
    $config['live_mode'],
    $config['default_currency']
);

// Run the test
if (testPaymentCreation($netopia)) {
    echo "\nTest completed successfully! The payment form data was generated correctly.\n";
    echo "This indicates that the encryption is working properly with the current cipher settings.\n";
} else {
    echo "\nTest failed. Please check the error message above for details.\n";
}
