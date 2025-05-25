<?php

use Aflorea4\NetopiaPayments\Models\Response;

it('can check if payment is successful', function () {
    $response = new Response();
    $response->action = 'confirmed';
    $response->errorCode = null;
    
    expect($response->isSuccessful())->toBeTrue();
});

it('can check if payment is not successful with error code', function () {
    $response = new Response();
    $response->action = 'confirmed';
    $response->errorCode = '100';
    
    expect($response->isSuccessful())->toBeFalse();
});

it('can check if payment is pending', function () {
    $response = new Response();
    $response->action = 'confirmed_pending';
    
    expect($response->isPending())->toBeTrue();
    expect($response->isSuccessful())->toBeFalse();
});

it('can check if payment is paid', function () {
    $response = new Response();
    $response->action = 'paid';
    
    expect($response->isPaid())->toBeTrue();
    expect($response->isSuccessful())->toBeFalse();
});

it('can check if payment is canceled', function () {
    $response = new Response();
    $response->action = 'canceled';
    
    expect($response->isCanceled())->toBeTrue();
    expect($response->isSuccessful())->toBeFalse();
});

it('can check if payment is credited', function () {
    $response = new Response();
    $response->action = 'credit';
    
    expect($response->isCredited())->toBeTrue();
    expect($response->isSuccessful())->toBeFalse();
});
