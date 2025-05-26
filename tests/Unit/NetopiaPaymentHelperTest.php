<?php

use Aflorea4\NetopiaPayments\Helpers\NetopiaPaymentHelper;
use Aflorea4\NetopiaPayments\Models\Request;
use Aflorea4\NetopiaPayments\Models\Invoice;
use Aflorea4\NetopiaPayments\Models\BillingAddress;
use Illuminate\Support\Facades\Config;
use Tests\TestHelper;

beforeEach(function () {
    // Mock the Config facade to use our test certificates
    Config::shouldReceive('get')
        ->with('netopia.signature')
        ->andReturn(TestHelper::getTestSignature());
    
    Config::shouldReceive('get')
        ->with('netopia.public_key_path')
        ->andReturn(TestHelper::getTestPublicKeyPath());
    
    Config::shouldReceive('get')
        ->with('netopia.private_key_path')
        ->andReturn(TestHelper::getTestPrivateKeyPath());
});

it('can generate payment form data for a request', function () {
    // Create a test request
    $request = new Request();
    $request->signature = TestHelper::getTestSignature();
    $request->orderId = 'TEST-' . time();
    $request->returnUrl = 'https://example.com/return';
    $request->confirmUrl = 'https://example.com/confirm';
    
    // Create a billing address
    $billingAddress = new BillingAddress();
    $billingAddress->type = 'person';
    $billingAddress->firstName = 'John';
    $billingAddress->lastName = 'Doe';
    $billingAddress->email = 'john.doe@example.com';
    $billingAddress->address = '123 Main St';
    $billingAddress->mobilePhone = '1234567890';
    
    // Create an invoice
    $invoice = new Invoice();
    $invoice->amount = 1.00;
    $invoice->currency = 'RON';
    $invoice->details = 'Test payment';
    $invoice->billingAddress = $billingAddress;
    
    // Set the invoice in the request
    $request->invoice = $invoice;
    
    // Generate payment form data
    $paymentFormData = NetopiaPaymentHelper::generatePaymentFormData(
        $request, 
        TestHelper::getTestSignature(), 
        TestHelper::getTestPublicKeyPath(),
        false // Use sandbox mode
    );
    
    // Verify the payment form data
    expect($paymentFormData)->toBeArray();
    expect($paymentFormData)->toHaveKeys(['env_key', 'data', 'cipher', 'url']);
    
    // The URL should be the sandbox URL
    expect($paymentFormData['url'])->toContain('sandboxsecure.mobilpay.ro');
    
    // The cipher should be specified
    expect($paymentFormData['cipher'])->toBeString();
    
    // The env_key and data should be base64 encoded
    expect(base64_decode($paymentFormData['env_key'], true))->not->toBeFalse();
    expect(base64_decode($paymentFormData['data'], true))->not->toBeFalse();
});

it('can generate a payment response XML', function () {
    // Generate a success response
    $successResponse = NetopiaPaymentHelper::generatePaymentResponse();
    
    // Verify the success response
    expect($successResponse)->toBeString();
    expect($successResponse)->toContain('<?xml version="1.0" encoding="utf-8"?>');
    expect($successResponse)->toContain('<crc>OK</crc>');
    
    // Generate an error response
    $errorResponse = NetopiaPaymentHelper::generatePaymentResponse(1, 100, 'Error message');
    
    // Verify the error response
    expect($errorResponse)->toBeString();
    expect($errorResponse)->toContain('<?xml version="1.0" encoding="utf-8"?>');
    expect($errorResponse)->toContain('<crc error_type="1" error_code="100">Error message</crc>');
});
