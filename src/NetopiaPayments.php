<?php

namespace Aflorea4\NetopiaPayments;

use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Config;
use Aflorea4\NetopiaPayments\Models\Address;
use Aflorea4\NetopiaPayments\Models\Invoice;
use Aflorea4\NetopiaPayments\Models\Request;
use Aflorea4\NetopiaPayments\Models\Response;
use Aflorea4\NetopiaPayments\Helpers\PaymentFormGenerator;
use Aflorea4\NetopiaPayments\Helpers\NetopiaPaymentEncryption;

class NetopiaPayments
{
    /**
     * The Netopia signature (merchant identifier)
     *
     * @var string
     */
    protected $signature;

    /**
     * The public key path
     *
     * @var string
     */
    protected $publicKeyPath;

    /**
     * The private key path
     *
     * @var string
     */
    protected $privateKeyPath;

    /**
     * The live mode flag
     *
     * @var bool
     */
    protected $liveMode;

    /**
     * Create a new NetopiaPayments instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->signature = Config::get('netopia.signature');
        $this->publicKeyPath = Config::get('netopia.public_key_path');
        $this->privateKeyPath = Config::get('netopia.private_key_path');
        $this->liveMode = Config::get('netopia.live_mode', false);
    }

    /**
     * Create a payment request
     *
     * @param string $orderId
     * @param float $amount
     * @param string $currency
     * @param string $returnUrl
     * @param string $confirmUrl
     * @param array $billingDetails
     * @param string $description
     * @return array
     * @throws Exception
     */
    public function createPaymentRequest(
        string $orderId,
        float $amount,
        string $currency,
        string $returnUrl,
        string $confirmUrl,
        array $billingDetails,
        string $description = ''
    ) {
        // Create a new payment request
        $request = new Request();
        $request->signature = $this->signature;
        $request->orderId = $orderId;
        $request->returnUrl = $returnUrl;
        $request->confirmUrl = $confirmUrl;

        // Create invoice
        $invoice = new Invoice();
        $invoice->currency = $currency;
        $invoice->amount = $amount;
        $invoice->details = $description;

        // Set billing details
        $billingAddress = new Address();
        $billingAddress->type = $billingDetails['type'] ?? 'person';
        $billingAddress->firstName = $billingDetails['firstName'] ?? '';
        $billingAddress->lastName = $billingDetails['lastName'] ?? '';
        $billingAddress->email = $billingDetails['email'] ?? '';
        $billingAddress->address = $billingDetails['address'] ?? '';
        $billingAddress->mobilePhone = $billingDetails['mobilePhone'] ?? '';

        $invoice->setBillingAddress($billingAddress);
        $request->invoice = $invoice;

        // Generate the payment form data
        return $this->generatePaymentFormData($request);
    }

    /**
     * Generate the payment form data
     *
     * @param Request $request
     * @return array
     * @throws Exception
     */
    protected function generatePaymentFormData(Request $request)
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
        $encryptedData = NetopiaPaymentEncryption::encrypt($xmlString, $this->signature, $this->publicKeyPath);

        // Return the payment form data
        $result = [
            'url' => $this->getPaymentUrl(),
            'env_key' => $encryptedData['env_key'],
            'data' => $encryptedData['data'],
            'cipher' => $encryptedData['cipher'],
        ];
        
        // Add IV if using AES-256-CBC
        if ($encryptedData['cipher'] === 'aes-256-cbc' && isset($encryptedData['iv'])) {
            $result['iv'] = $encryptedData['iv'];
        }
        
        return $result;
    }

    /**
     * Process the response from Netopia Payments
     *
     * @param string $envKey The envelope key
     * @param string $data The encrypted data
     * @param string $cipher The cipher used for encryption
     * @param string|null $errorCode The error code if any
     * @param string|null $iv The initialization vector (for AES-256-CBC)
     * @return Response The response object
     */
    public function processResponse($envKey, $data, $cipher = 'rc4', $errorCode = null, $iv = null)
    {
        $response = new Response();
        
        // Handle error case
        if (!empty($errorCode)) {
            $response->errorCode = $errorCode;
            return $response;
        }
        
        try {
            // Decrypt the data
            $decryptedData = NetopiaPaymentEncryption::decrypt(
                $envKey,
                $data,
                $this->signature,
                $this->privateKeyPath,
                $cipher,
                $iv
            );
            
            // Parse the XML data
            $xmlDoc = new DOMDocument();
            $xmlDoc->loadXML($decryptedData);
            
            // Get the order element
            $orderElem = $xmlDoc->getElementsByTagName('order')->item(0);
            $response->orderId = $orderElem->getAttribute('id');
            
            // Get the mobilpay element
            $mobilpayElem = $xmlDoc->getElementsByTagName('mobilpay')->item(0);
            
            // Get the action
            $actionElem = $mobilpayElem->getElementsByTagName('action')->item(0);
            if ($actionElem) {
                $response->action = $actionElem->nodeValue;
            }
            
            // Get the error element if it exists
            $errorElem = $mobilpayElem->getElementsByTagName('error')->item(0);
            if ($errorElem) {
                $response->errorCode = $errorElem->getAttribute('code');
                $response->errorMessage = $errorElem->nodeValue;
            }
            
            // Get the timestamp if it exists
            $timestampElem = $mobilpayElem->getElementsByTagName('timestamp')->item(0);
            if ($timestampElem) {
                $response->timestamp = $timestampElem->nodeValue;
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
            
            // Get the invoice details if they exist
            $invoiceElem = $xmlDoc->getElementsByTagName('invoice')->item(0);
            if ($invoiceElem) {
                $response->invoiceId = $invoiceElem->getAttribute('id');
                $response->invoiceAmount = (float) $invoiceElem->getAttribute('amount');
                $response->invoiceCurrency = $invoiceElem->getAttribute('currency');
            }
            
        } catch (Exception $e) {
            $response->errorCode = 'ERR999';
            $response->errorMessage = $e->getMessage();
        }
        
        return $response;
    }

    /**
     * Get the payment URL
     *
     * @return string
     */
    protected function getPaymentUrl()
    {
        return $this->liveMode
            ? 'https://secure.mobilpay.ro'
            : 'https://sandboxsecure.mobilpay.ro';
    }

    /**
     * Generate the payment response for Netopia
     *
     * @param int $errorType
     * @param int $errorCode
     * @param string $message
     * @return string
     */
    public function generatePaymentResponse(int $errorType = 0, int $errorCode = 0, string $message = 'OK')
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
}
