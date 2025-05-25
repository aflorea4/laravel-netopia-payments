<?php

use Aflorea4\NetopiaPayments\Models\BillingAddress;

it('can be instantiated with default values', function () {
    $billingAddress = new BillingAddress();
    
    expect($billingAddress)->toBeInstanceOf(BillingAddress::class);
    expect($billingAddress->type)->toBeNull();
    expect($billingAddress->firstName)->toBeNull();
    expect($billingAddress->lastName)->toBeNull();
    expect($billingAddress->email)->toBeNull();
    expect($billingAddress->address)->toBeNull();
    expect($billingAddress->mobilePhone)->toBeNull();
});

it('can set and get properties for person type', function () {
    $billingAddress = new BillingAddress();
    
    // Set properties for a person
    $billingAddress->type = 'person';
    $billingAddress->firstName = 'John';
    $billingAddress->lastName = 'Doe';
    $billingAddress->email = 'john.doe@example.com';
    $billingAddress->address = '123 Main St';
    $billingAddress->mobilePhone = '1234567890';
    
    // Verify properties
    expect($billingAddress->type)->toBe('person');
    expect($billingAddress->firstName)->toBe('John');
    expect($billingAddress->lastName)->toBe('Doe');
    expect($billingAddress->email)->toBe('john.doe@example.com');
    expect($billingAddress->address)->toBe('123 Main St');
    expect($billingAddress->mobilePhone)->toBe('1234567890');
});

it('can set and get properties for company type', function () {
    $billingAddress = new BillingAddress();
    
    // Set properties for a company
    $billingAddress->type = 'company';
    $billingAddress->firstName = 'Acme';
    $billingAddress->lastName = 'Inc';
    $billingAddress->email = 'contact@acme.com';
    $billingAddress->address = '456 Business Ave';
    $billingAddress->mobilePhone = '9876543210';
    
    // Verify properties
    expect($billingAddress->type)->toBe('company');
    expect($billingAddress->firstName)->toBe('Acme');
    expect($billingAddress->lastName)->toBe('Inc');
    expect($billingAddress->email)->toBe('contact@acme.com');
    expect($billingAddress->address)->toBe('456 Business Ave');
    expect($billingAddress->mobilePhone)->toBe('9876543210');
});
