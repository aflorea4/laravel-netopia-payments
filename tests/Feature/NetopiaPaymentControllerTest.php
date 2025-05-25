<?php

use Aflorea4\NetopiaPayments\Http\Controllers\NetopiaPaymentController;
use Aflorea4\NetopiaPayments\Facades\NetopiaPayments;
use Aflorea4\NetopiaPayments\Models\Response as NetopiaResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Aflorea4\NetopiaPayments\Events\NetopiaPaymentConfirmed;
use Aflorea4\NetopiaPayments\Events\NetopiaPaymentPending;
use Aflorea4\NetopiaPayments\Events\NetopiaPaymentCanceled;

beforeEach(function () {
    $this->controller = new NetopiaPaymentController();
});

it('handles confirm request and returns success response', function () {
    // Mock the NetopiaPayments facade
    NetopiaPayments::shouldReceive('processResponse')
        ->once()
        ->with('test-env-key', 'test-data', 'felix-rc4', 'test-iv')
        ->andReturn(createSuccessfulResponse());
    
    NetopiaPayments::shouldReceive('generatePaymentResponse')
        ->once()
        ->andReturn('<?xml version="1.0" encoding="utf-8"?><crc>OK</crc>');
    
    // Mock the event dispatcher
    Event::fake();
    
    // Create a mock request
    $request = Request::create('/netopia/confirm', 'POST', [
        'env_key' => 'test-env-key',
        'data' => 'test-data',
        'cipher' => 'felix-rc4',
        'iv' => 'test-iv',
    ]);
    
    // Call the controller method
    $response = $this->controller->confirm($request);
    
    // Assert the response
    expect($response->getContent())->toBe('<?xml version="1.0" encoding="utf-8"?><crc>OK</crc>');
    expect($response->getStatusCode())->toBe(200);
    
    // Assert that the event was dispatched
    Event::assertDispatched(NetopiaPaymentConfirmed::class);
});

it('handles confirm request and returns pending response', function () {
    // Mock the NetopiaPayments facade
    NetopiaPayments::shouldReceive('processResponse')
        ->once()
        ->with('test-env-key', 'test-data', 'felix-rc4', 'test-iv')
        ->andReturn(createPendingResponse());
    
    NetopiaPayments::shouldReceive('generatePaymentResponse')
        ->once()
        ->andReturn('<?xml version="1.0" encoding="utf-8"?><crc>OK</crc>');
    
    // Mock the event dispatcher
    Event::fake();
    
    // Create a mock request
    $request = Request::create('/netopia/confirm', 'POST', [
        'env_key' => 'test-env-key',
        'data' => 'test-data',
        'cipher' => 'felix-rc4',
        'iv' => 'test-iv',
    ]);
    
    // Call the controller method
    $response = $this->controller->confirm($request);
    
    // Assert the response
    expect($response->getContent())->toBe('<?xml version="1.0" encoding="utf-8"?><crc>OK</crc>');
    expect($response->getStatusCode())->toBe(200);
    
    // Assert that the event was dispatched
    Event::assertDispatched(NetopiaPaymentPending::class);
});

it('handles confirm request and returns canceled response', function () {
    // Mock the NetopiaPayments facade
    NetopiaPayments::shouldReceive('processResponse')
        ->once()
        ->with('test-env-key', 'test-data', 'felix-rc4', 'test-iv')
        ->andReturn(createCanceledResponse());
    
    NetopiaPayments::shouldReceive('generatePaymentResponse')
        ->once()
        ->andReturn('<?xml version="1.0" encoding="utf-8"?><crc>OK</crc>');
    
    // Mock the event dispatcher
    Event::fake();
    
    // Create a mock request
    $request = Request::create('/netopia/confirm', 'POST', [
        'env_key' => 'test-env-key',
        'data' => 'test-data',
        'cipher' => 'felix-rc4',
        'iv' => 'test-iv',
    ]);
    
    // Call the controller method
    $response = $this->controller->confirm($request);
    
    // Assert the response
    expect($response->getContent())->toBe('<?xml version="1.0" encoding="utf-8"?><crc>OK</crc>');
    expect($response->getStatusCode())->toBe(200);
    
    // Assert that the event was dispatched
    Event::assertDispatched(NetopiaPaymentCanceled::class);
});

it('handles error in confirm request', function () {
    // Mock the NetopiaPayments facade
    NetopiaPayments::shouldReceive('processResponse')
        ->once()
        ->with('test-env-key', 'test-data', 'felix-rc4', 'test-iv')
        ->andThrow(new Exception('Test error'));
    
    NetopiaPayments::shouldReceive('generatePaymentResponse')
        ->once()
        ->andReturn('<?xml version="1.0" encoding="utf-8"?><crc error_type="1" error_code="1">Test error</crc>');
    
    // Create a mock request
    $request = Request::create('/netopia/confirm', 'POST', [
        'env_key' => 'test-env-key',
        'data' => 'test-data',
        'cipher' => 'felix-rc4',
        'iv' => 'test-iv',
    ]);
    
    // Call the controller method
    $response = $this->controller->confirm($request);
    
    // Assert the response
    expect($response->getContent())->toBe('<?xml version="1.0" encoding="utf-8"?><crc error_type="1" error_code="1">Test error</crc>');
    expect($response->getStatusCode())->toBe(200);
});

// Helper functions to create test responses
function createSuccessfulResponse(): NetopiaResponse
{
    $response = new NetopiaResponse();
    $response->orderId = 'TEST-ORDER-123';
    $response->action = 'confirmed';
    $response->errorCode = null;
    $response->errorMessage = null;
    $response->processedAmount = 100.00;
    $response->originalAmount = 100.00;
    
    return $response;
}

function createPendingResponse(): NetopiaResponse
{
    $response = new NetopiaResponse();
    $response->orderId = 'TEST-ORDER-123';
    $response->action = 'confirmed_pending';
    $response->errorCode = null;
    $response->errorMessage = null;
    $response->processedAmount = 100.00;
    $response->originalAmount = 100.00;
    
    return $response;
}

function createCanceledResponse(): NetopiaResponse
{
    $response = new NetopiaResponse();
    $response->orderId = 'TEST-ORDER-123';
    $response->action = 'canceled';
    $response->errorCode = null;
    $response->errorMessage = null;
    $response->processedAmount = 0.00;
    $response->originalAmount = 100.00;
    
    return $response;
}
