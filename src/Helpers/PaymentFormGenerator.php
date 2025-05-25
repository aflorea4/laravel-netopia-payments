<?php

namespace Aflorea4\NetopiaPayments\Helpers;

use Illuminate\Support\Facades\View;
use Aflorea4\NetopiaPayments\Facades\NetopiaPayments;

class PaymentFormGenerator
{
    /**
     * Generate a payment form for Netopia Payments
     *
     * @param string $orderId
     * @param float $amount
     * @param array $billingDetails
     * @param string $description
     * @param string $currency
     * @param string|null $returnUrl
     * @param string|null $confirmUrl
     * @return \Illuminate\View\View
     */
    public static function generateForm(
        string $orderId,
        float $amount,
        array $billingDetails,
        string $description = '',
        string $currency = null,
        string $returnUrl = null,
        string $confirmUrl = null
    ) {
        // Set default currency if not provided
        $currency = $currency ?? config('netopia.default_currency', 'RON');
        
        // Set default URLs if not provided
        $returnUrl = $returnUrl ?? route('netopia.return');
        $confirmUrl = $confirmUrl ?? route('netopia.confirm');
        
        // Create payment request
        $paymentData = NetopiaPayments::createPaymentRequest(
            $orderId,
            $amount,
            $currency,
            $returnUrl,
            $confirmUrl,
            $billingDetails,
            $description
        );
        
        // Return the payment form view
        return View::make('netopia::payment-form', [
            'paymentUrl' => $paymentData['url'],
            'envKey' => $paymentData['env_key'],
            'data' => $paymentData['data'],
            'iv' => $paymentData['iv'],
        ]);
    }
    
    /**
     * Render a payment button that opens the payment form in a new window
     *
     * @param string $orderId
     * @param float $amount
     * @param array $billingDetails
     * @param string $buttonText
     * @param string $description
     * @param string $currency
     * @param string|null $returnUrl
     * @param string|null $confirmUrl
     * @return string
     */
    public static function renderPaymentButton(
        string $orderId,
        float $amount,
        array $billingDetails,
        string $buttonText = 'Pay Now',
        string $description = '',
        string $currency = null,
        string $returnUrl = null,
        string $confirmUrl = null
    ) {
        // Set default currency if not provided
        $currency = $currency ?? config('netopia.default_currency', 'RON');
        
        // Set default URLs if not provided
        $returnUrl = $returnUrl ?? route('netopia.return');
        $confirmUrl = $confirmUrl ?? route('netopia.confirm');
        
        // Create payment request
        $paymentData = NetopiaPayments::createPaymentRequest(
            $orderId,
            $amount,
            $currency,
            $returnUrl,
            $confirmUrl,
            $billingDetails,
            $description
        );
        
        // Generate a unique form ID
        $formId = 'netopia-form-' . uniqid();
        
        // Create the HTML for the payment button and form
        $html = '<form id="' . $formId . '" action="' . $paymentData['url'] . '" method="post" target="_blank" style="display:none;">';
        $html .= '<input type="hidden" name="env_key" value="' . $paymentData['env_key'] . '">';
        $html .= '<input type="hidden" name="data" value="' . $paymentData['data'] . '">';
        $html .= '<input type="hidden" name="iv" value="' . $paymentData['iv'] . '">';
        $html .= '</form>';
        $html .= '<button type="button" onclick="document.getElementById(\'' . $formId . '\').submit();" class="netopia-payment-button">' . $buttonText . '</button>';
        
        return $html;
    }
}
