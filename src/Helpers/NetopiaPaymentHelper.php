<?php

namespace Aflorea4\NetopiaPayments\Helpers;

use Exception;
use Aflorea4\NetopiaPayments\Models\Request;
use Aflorea4\NetopiaPayments\Models\Response;
use DOMDocument;

/**
 * Netopia Payment Helper
 * This class provides helper methods for Netopia Payments integration
 */
class NetopiaPaymentHelper
{
    /**
     * Generate payment form data
     *
     * @param Request $request The payment request
     * @param string $signature The Netopia merchant signature
     * @param string $publicKeyPath Path to the public key file
     * @param bool $liveMode Whether to use live mode
     * @return array The payment form data
     * @throws Exception
     */
    public static function generatePaymentFormData(Request $request, $signature, $publicKeyPath, $liveMode = false)
    {
        // Create XML document
        $xmlDoc = new DOMDocument('1.0', 'utf-8');
        $xmlDoc->formatOutput = true;

        // Create the order element
        $orderElem = $xmlDoc->createElement('order');
        $orderElem->setAttribute('type', 'card');
        $orderElem->setAttribute('id', $request->orderId);
        $orderElem->setAttribute('timestamp', date('YmdHis'));
        $xmlDoc->appendChild($orderElem);

        // Add signature
        $signatureElem = $xmlDoc->createElement('signature', $request->signature);
        $orderElem->appendChild($signatureElem);

        // Add invoice
        $invoiceElem = $xmlDoc->createElement('invoice');
        $invoiceElem->setAttribute('currency', $request->invoice->currency);
        $invoiceElem->setAttribute('amount', number_format($request->invoice->amount, 2, '.', ''));
        $orderElem->appendChild($invoiceElem);

        // Add invoice details
        $invoiceElem->appendChild($xmlDoc->createElement('details', $request->invoice->details));

        // Add contact info
        $contactInfoElem = $xmlDoc->createElement('contact_info');
        $orderElem->appendChild($contactInfoElem);

        // Add billing address
        $billingAddress = $request->invoice->billingAddress;
        $billingElem = $xmlDoc->createElement('billing');
        $contactInfoElem->appendChild($billingElem);

        // Add billing address details
        $billingElem->setAttribute('type', $billingAddress->type);
        $billingElem->appendChild($xmlDoc->createElement('first_name', $billingAddress->firstName));
        $billingElem->appendChild($xmlDoc->createElement('last_name', $billingAddress->lastName));
        $billingElem->appendChild($xmlDoc->createElement('email', $billingAddress->email));
        $billingElem->appendChild($xmlDoc->createElement('address', $billingAddress->address));
        $billingElem->appendChild($xmlDoc->createElement('mobile_phone', $billingAddress->mobilePhone));

        // Add URL elements
        $urlElem = $xmlDoc->createElement('url');
        $orderElem->appendChild($urlElem);

        // Add return URL
        $urlElem->appendChild($xmlDoc->createElement('return', $request->returnUrl));

        // Add confirm URL
        $urlElem->appendChild($xmlDoc->createElement('confirm', $request->confirmUrl));

        // Get the XML as string
        $xmlString = $xmlDoc->saveXML();

        // Encrypt the XML
        $encryptedData = NetopiaPaymentEncryption::encrypt($xmlString, $signature, $publicKeyPath);

        // Return the payment form data
        return [
            'env_key' => $encryptedData['env_key'],
            'data' => $encryptedData['data'],
            'cipher' => $encryptedData['cipher'],
            'url' => self::getPaymentUrl($liveMode),
        ];
    }

    /**
     * Process the payment response
     *
     * @param string $envKey The envelope key
     * @param string $data The encrypted data
     * @param string $signature The Netopia merchant signature
     * @param string $privateKeyPath Path to the private key file
     * @param string $cipher The cipher used for encryption
     * @param string|null $iv The initialization vector for AES (base64 encoded)
     * @return Response The payment response
     * @throws Exception
     */
    public static function processResponse($envKey, $data, $signature, $privateKeyPath, $cipher = 'aes-256-cbc', $iv = null)
    {
        // Decrypt the data
        $decryptedData = NetopiaPaymentEncryption::decrypt($envKey, $data, $signature, $privateKeyPath, $cipher, $iv);

        // Parse the XML
        $xmlDoc = new DOMDocument();
        $xmlDoc->loadXML($decryptedData);

        // Create a new response object
        $response = new Response();

        // Get the order element
        $orderElem = $xmlDoc->getElementsByTagName('order')->item(0);
        $response->orderId = $orderElem->getAttribute('id');

        // Get the mobilpay element
        $mobilpayElem = $xmlDoc->getElementsByTagName('mobilpay')->item(0);
        
        // Get the action
        $actionElem = $mobilpayElem->getElementsByTagName('action')->item(0);
        $response->action = $actionElem->nodeValue;

        // Get the error element if it exists
        $errorElem = $mobilpayElem->getElementsByTagName('error')->item(0);
        if ($errorElem) {
            $response->errorCode = $errorElem->getAttribute('code');
            $response->errorMessage = $errorElem->nodeValue;
        }

        // Get the processed amount
        $processedAmountElem = $mobilpayElem->getElementsByTagName('processed_amount')->item(0);
        if ($processedAmountElem) {
            $response->processedAmount = (float) $processedAmountElem->nodeValue;
        }

        // Get the original amount
        $originalAmountElem = $mobilpayElem->getElementsByTagName('original_amount')->item(0);
        if ($originalAmountElem) {
            $response->originalAmount = (float) $originalAmountElem->nodeValue;
        }

        return $response;
    }

    /**
     * Generate the payment response for Netopia
     *
     * @param int $errorType The error type
     * @param int $errorCode The error code
     * @param string $message The message
     * @return string The XML response
     */
    public static function generatePaymentResponse($errorType = 0, $errorCode = 0, $message = 'OK')
    {
        $xmlDoc = new DOMDocument('1.0', 'utf-8');
        $xmlDoc->formatOutput = true;

        $crcElem = $xmlDoc->createElement('crc', $message);
        
        if ($errorType > 0) {
            $crcElem->setAttribute('error_type', $errorType);
            $crcElem->setAttribute('error_code', $errorCode);
        }
        
        $xmlDoc->appendChild($crcElem);
        
        return $xmlDoc->saveXML();
    }

    /**
     * Get the payment URL
     *
     * @param bool $liveMode Whether to use live mode
     * @return string The payment URL
     */
    private static function getPaymentUrl($liveMode)
    {
        return $liveMode
            ? 'https://secure.mobilpay.ro'
            : 'https://sandboxsecure.mobilpay.ro';
    }
}
