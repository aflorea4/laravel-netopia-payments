<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aflorea4\NetopiaPayments\Facades\NetopiaPayments;
use Aflorea4\NetopiaPayments\Helpers\PaymentFormGenerator;

class PaymentController extends Controller
{
    /**
     * Show the checkout page
     *
     * @return \Illuminate\View\View
     */
    public function checkout()
    {
        // Example order data
        $order = [
            'id' => 'ORD-' . time(),
            'amount' => 100.00,
            'description' => 'Order #' . time(),
        ];
        
        // Example customer data
        $customer = [
            'type' => 'person',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'address' => '123 Main St, Bucharest',
            'mobilePhone' => '0712345678',
        ];
        
        return view('checkout', compact('order', 'customer'));
    }
    
    /**
     * Process the payment
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function processPayment(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'order_id' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:255',
            'mobile_phone' => 'required|string|max:255',
        ]);
        
        // Create billing details array
        $billingDetails = [
            'type' => 'person',
            'firstName' => $validated['first_name'],
            'lastName' => $validated['last_name'],
            'email' => $validated['email'],
            'address' => $validated['address'],
            'mobilePhone' => $validated['mobile_phone'],
        ];
        
        // Method 1: Using the NetopiaPayments facade directly
        $paymentData = NetopiaPayments::createPaymentRequest(
            $validated['order_id'],
            $validated['amount'],
            'RON', // Currency
            route('payment.return'), // Return URL
            route('netopia.confirm'), // Confirm URL
            $billingDetails,
            'Payment for Order #' . $validated['order_id'] // Description
        );
        
        return view('payment.redirect', [
            'paymentData' => $paymentData,
        ]);
        
        // Method 2: Using the PaymentFormGenerator helper
        // return PaymentFormGenerator::generateForm(
        //     $validated['order_id'],
        //     $validated['amount'],
        //     $billingDetails,
        //     'Payment for Order #' . $validated['order_id']
        // );
    }
    
    /**
     * Handle the payment success
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function success(Request $request)
    {
        return view('payment.success', [
            'order_id' => $request->input('order_id'),
        ]);
    }
    
    /**
     * Handle the payment failure
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function failed(Request $request)
    {
        return view('payment.failed', [
            'order_id' => $request->input('order_id'),
            'error_code' => $request->input('error_code'),
            'error_message' => $request->input('error_message'),
        ]);
    }
    
    /**
     * Handle the payment pending
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function pending(Request $request)
    {
        return view('payment.pending', [
            'order_id' => $request->input('order_id'),
        ]);
    }
}
