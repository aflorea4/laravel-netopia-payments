<?php

use Aflorea4\NetopiaPayments\Models\Request;
use Aflorea4\NetopiaPayments\Models\Invoice;
use Aflorea4\NetopiaPayments\Models\BillingAddress;

it('can be instantiated with default values', function () {
    $request = new Request();
    
    expect($request)->toBeInstanceOf(Request::class);
    expect($request->signature)->toBeNull();
    expect($request->orderId)->toBeNull();
    expect($request->returnUrl)->toBeNull();
    expect($request->confirmUrl)->toBeNull();
    expect($request->invoice)->toBeNull();
});

it('can set and get properties', function () {
    $request = new Request();
    
    // Set properties
    $request->signature = 'TEST-SIGNATURE';
    $request->orderId = 'TEST-ORDER-123';
    $request->returnUrl = 'https://example.com/return';
    $request->confirmUrl = 'https://example.com/confirm';
    
    // Verify properties
    expect($request->signature)->toBe('TEST-SIGNATURE');
    expect($request->orderId)->toBe('TEST-ORDER-123');
    expect($request->returnUrl)->toBe('https://example.com/return');
    expect($request->confirmUrl)->toBe('https://example.com/confirm');
});

it('can set and get invoice', function () {
    $request = new Request();
    $invoice = new Invoice();
    
    // Set invoice amount and currency
    $invoice->amount = 100.00;
    $invoice->currency = 'RON';
    
    // Set invoice to request
    $request->invoice = $invoice;
    
    // Verify invoice
    expect($request->invoice)->toBeInstanceOf(Invoice::class);
    expect($request->invoice->amount)->toBe(100.00);
    expect($request->invoice->currency)->toBe('RON');
});

it('can create a complete payment request', function () {
    // Create a request
    $request = new Request();
    $request->signature = 'TEST-SIGNATURE';
    $request->orderId = 'TEST-ORDER-123';
    $request->returnUrl = 'https://example.com/return';
    $request->confirmUrl = 'https://example.com/confirm';
    
    // Create an invoice
    $invoice = new Invoice();
    $invoice->amount = 100.00;
    $invoice->currency = 'RON';
    $invoice->details = 'Test payment';
    
    // Create a billing address
    $billingAddress = new BillingAddress();
    $billingAddress->type = 'person';
    $billingAddress->firstName = 'John';
    $billingAddress->lastName = 'Doe';
    $billingAddress->email = 'john.doe@example.com';
    $billingAddress->address = '123 Main St';
    $billingAddress->mobilePhone = '1234567890';
    
    // Set billing address to invoice
    $invoice->billingAddress = $billingAddress;
    
    // Set invoice to request
    $request->invoice = $invoice;
    
    // Verify the complete request
    expect($request->signature)->toBe('TEST-SIGNATURE');
    expect($request->orderId)->toBe('TEST-ORDER-123');
    expect($request->returnUrl)->toBe('https://example.com/return');
    expect($request->confirmUrl)->toBe('https://example.com/confirm');
    expect($request->invoice)->toBeInstanceOf(Invoice::class);
    expect($request->invoice->amount)->toBe(100.00);
    expect($request->invoice->currency)->toBe('RON');
    expect($request->invoice->details)->toBe('Test payment');
    expect($request->invoice->billingAddress)->toBeInstanceOf(BillingAddress::class);
    expect($request->invoice->billingAddress->firstName)->toBe('John');
    expect($request->invoice->billingAddress->lastName)->toBe('Doe');
    expect($request->invoice->billingAddress->email)->toBe('john.doe@example.com');
});
