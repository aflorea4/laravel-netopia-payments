<?php

use Aflorea4\NetopiaPayments\Models\Invoice;
use Aflorea4\NetopiaPayments\Models\Address;

it('can set billing address', function () {
    $invoice = new Invoice();
    $address = new Address();
    
    $address->type = 'person';
    $address->firstName = 'John';
    $address->lastName = 'Doe';
    $address->email = 'john.doe@example.com';
    $address->address = '123 Main St';
    $address->mobilePhone = '0712345678';
    
    $invoice->setBillingAddress($address);
    
    expect($invoice->billingAddress)->toBe($address);
    expect($invoice->billingAddress->firstName)->toBe('John');
    expect($invoice->billingAddress->lastName)->toBe('Doe');
});
