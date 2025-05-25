<?php

use Aflorea4\NetopiaPayments\Http\Controllers\NetopiaPaymentController;
use Aflorea4\NetopiaPayments\Facades\NetopiaPayments;
use Aflorea4\NetopiaPayments\Models\Response as NetopiaResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->controller = new NetopiaPaymentController();
    
    // Define test routes that the controller will redirect to
    Route::get('/payment/success', function () {
        return 'success';
    })->name('payment.success');
    
    Route::get('/payment/pending', function () {
        return 'pending';
    })->name('payment.pending');
    
    Route::get('/payment/failed', function () {
        return 'failed';
    })->name('payment.failed');
});

it('handles return request and redirects to success page', function () {
    // Mock the NetopiaPayments facade
    NetopiaPayments::shouldReceive('processResponse')
        ->once()
        ->with('test-env-key', 'test-data', 'felix-rc4', 'test-iv')
        ->andReturn(createSuccessfulResponse());
    
    // Create a mock request
    $request = Request::create('/netopia/return', 'GET', [
        'env_key' => 'test-env-key',
        'data' => 'test-data',
        'cipher' => 'felix-rc4',
        'iv' => 'test-iv',
    ]);
    
    // Call the controller method
    $response = $this->controller->return($request);
    
    // Assert the response is a redirect to the success route
    expect($response->getTargetUrl())->toContain('/payment/success');
    expect($response->getTargetUrl())->toContain('order_id=TEST-ORDER-123');
});

it('handles return request and redirects to pending page', function () {
    // Mock the NetopiaPayments facade
    NetopiaPayments::shouldReceive('processResponse')
        ->once()
        ->with('test-env-key', 'test-data', 'felix-rc4', 'test-iv')
        ->andReturn(createPendingResponse());
    
    // Create a mock request
    $request = Request::create('/netopia/return', 'GET', [
        'env_key' => 'test-env-key',
        'data' => 'test-data',
        'cipher' => 'felix-rc4',
        'iv' => 'test-iv',
    ]);
    
    // Call the controller method
    $response = $this->controller->return($request);
    
    // Assert the response is a redirect to the pending route
    expect($response->getTargetUrl())->toContain('/payment/pending');
    expect($response->getTargetUrl())->toContain('order_id=TEST-ORDER-123');
});

it('handles return request and redirects to failed page', function () {
    // Create a failed response
    $failedResponse = new NetopiaResponse();
    $failedResponse->orderId = 'TEST-ORDER-123';
    $failedResponse->action = 'rejected';
    $failedResponse->errorCode = '99';
    $failedResponse->errorMessage = 'Payment rejected';
    
    // Mock the NetopiaPayments facade
    NetopiaPayments::shouldReceive('processResponse')
        ->once()
        ->with('test-env-key', 'test-data', 'felix-rc4', 'test-iv')
        ->andReturn($failedResponse);
    
    // Create a mock request
    $request = Request::create('/netopia/return', 'GET', [
        'env_key' => 'test-env-key',
        'data' => 'test-data',
        'cipher' => 'felix-rc4',
        'iv' => 'test-iv',
    ]);
    
    // Call the controller method
    $response = $this->controller->return($request);
    
    // Assert the response is a redirect to the failed route
    expect($response->getTargetUrl())->toContain('/payment/failed');
    expect($response->getTargetUrl())->toContain('order_id=TEST-ORDER-123');
    expect($response->getTargetUrl())->toContain('error_code=99');
});

it('handles error in return request', function () {
    // Mock the NetopiaPayments facade
    NetopiaPayments::shouldReceive('processResponse')
        ->once()
        ->with('test-env-key', 'test-data', 'felix-rc4', 'test-iv')
        ->andThrow(new Exception('Test error'));
    
    // Create a mock request
    $request = Request::create('/netopia/return', 'GET', [
        'env_key' => 'test-env-key',
        'data' => 'test-data',
        'cipher' => 'felix-rc4',
        'iv' => 'test-iv',
    ]);
    
    // Call the controller method
    $response = $this->controller->return($request);
    
    // Assert the response is a redirect to the failed route
    expect($response->getTargetUrl())->toContain('/payment/failed');
    // URL encoding changes + to %20, so we need to check for the encoded version
    expect($response->getTargetUrl())->toContain('error_message=Test%20error');
});

it('validates required parameters in return request', function () {
    // Create a mock request with missing parameters
    $request = Request::create('/netopia/return', 'GET', [
        // Missing all required parameters
    ]);
    
    // Mock the controller to validate parameters
    // Instead of expecting an exception, we'll check that the response is a redirect to the failed page
    $response = $this->controller->return($request);
    
    // Assert the response is a redirect to the failed route
    expect($response->getTargetUrl())->toContain('/payment/failed');
    expect($response->getTargetUrl())->toContain('error_message=Missing');
});

// Helper functions are already defined in NetopiaPaymentControllerTest.php
