<?php

use Aflorea4\NetopiaPayments\NetopiaPayments;
use Aflorea4\NetopiaPayments\Models\Address;
use Aflorea4\NetopiaPayments\Models\Invoice;
use Aflorea4\NetopiaPayments\Models\Request;
use Aflorea4\NetopiaPayments\Models\Response;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::shouldReceive('get')
        ->with('netopia.signature')
        ->andReturn('TEST-SIGNATURE');
    
    Config::shouldReceive('get')
        ->with('netopia.public_key_path')
        ->andReturn(__DIR__ . '/../stubs/public.cer');
    
    Config::shouldReceive('get')
        ->with('netopia.private_key_path')
        ->andReturn(__DIR__ . '/../stubs/private.key');
    
    Config::shouldReceive('get')
        ->with('netopia.live_mode', false)
        ->andReturn(false);
});

it('can be instantiated', function () {
    $netopiaPayments = new NetopiaPayments();
    
    expect($netopiaPayments)->toBeInstanceOf(NetopiaPayments::class);
});

it('generates a payment response', function () {
    $netopiaPayments = new NetopiaPayments();
    
    $response = $netopiaPayments->generatePaymentResponse();
    
    expect($response)->toBeString()
        ->toContain('<?xml version="1.0" encoding="utf-8"?>')
        ->toContain('<crc>OK</crc>');
});

it('generates a payment response with error', function () {
    $netopiaPayments = new NetopiaPayments();
    
    $response = $netopiaPayments->generatePaymentResponse(1, 100, 'Error message');
    
    expect($response)->toBeString()
        ->toContain('<?xml version="1.0" encoding="utf-8"?>')
        ->toContain('<crc error_type="1" error_code="100">Error message</crc>');
});
