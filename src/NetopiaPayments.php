<?php

namespace Aflorea4\NetopiaPayments;

use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Config;
use Aflorea4\NetopiaPayments\Models\Address;
use Aflorea4\NetopiaPayments\Models\Invoice;
use Aflorea4\NetopiaPayments\Models\Request;
use Aflorea4\NetopiaPayments\Models\Response;

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
        $detailsElem = $xmlDoc->createElement('details', $request->invoice->details);
        $invoiceElem->appendChild($detailsElem);

        // Add contact info
        $contactInfoElem = $xmlDoc->createElement('contact_info');
        $invoiceElem->appendChild($contactInfoElem);

        // Add billing address
        $billingElem = $xmlDoc->createElement('billing');
        $billingElem->setAttribute('type', $request->invoice->billingAddress->type);
        $contactInfoElem->appendChild($billingElem);

        // Add billing details
        $firstNameElem = $xmlDoc->createElement('first_name', $request->invoice->billingAddress->firstName);
        $billingElem->appendChild($firstNameElem);

        $lastNameElem = $xmlDoc->createElement('last_name', $request->invoice->billingAddress->lastName);
        $billingElem->appendChild($lastNameElem);

        $emailElem = $xmlDoc->createElement('email', $request->invoice->billingAddress->email);
        $billingElem->appendChild($emailElem);

        $addressElem = $xmlDoc->createElement('address', $request->invoice->billingAddress->address);
        $billingElem->appendChild($addressElem);

        $mobilePhoneElem = $xmlDoc->createElement('mobile_phone', $request->invoice->billingAddress->mobilePhone);
        $billingElem->appendChild($mobilePhoneElem);

        // Add URL section
        $urlElem = $xmlDoc->createElement('url');
        $orderElem->appendChild($urlElem);

        // Add confirm URL
        $confirmUrlElem = $xmlDoc->createElement('confirm', $request->confirmUrl);
        $urlElem->appendChild($confirmUrlElem);

        // Add return URL
        $returnUrlElem = $xmlDoc->createElement('return', $request->returnUrl);
        $urlElem->appendChild($returnUrlElem);

        // Convert XML to string
        $xmlString = $xmlDoc->saveXML();

        // Encrypt the data
        $encryptedData = $this->encrypt($xmlString);

        // Return the form data
        return [
            'env_key' => $encryptedData['env_key'],
            'data' => $encryptedData['data'],
            'url' => $this->getPaymentUrl(),
        ];
    }

    /**
     * Encrypt the data using the public key
     *
     * @param string $data
     * @return array
     * @throws Exception
     */
    protected function encrypt(string $data)
    {
        // Read the public key
        $publicKey = openssl_pkey_get_public(file_get_contents($this->publicKeyPath));
        if ($publicKey === false) {
            throw new Exception('Could not read public key');
        }

        // Encrypt the data
        $encryptedData = '';
        $envKeys = [];
        if (!openssl_seal($data, $encryptedData, $envKeys, [$publicKey], 'AES256')) {
            throw new Exception('Could not encrypt data');
        }

        // Free the key
        openssl_free_key($publicKey);

        // Return the encrypted data
        return [
            'env_key' => base64_encode($envKeys[0]),
            'data' => base64_encode($encryptedData),
        ];
    }

    /**
     * Decrypt the response data using the private key
     *
     * @param string $envKey
     * @param string $data
     * @return string
     * @throws Exception
     */
    protected function decrypt(string $envKey, string $data)
    {
        // Decode the data
        $envKey = base64_decode($envKey);
        $data = base64_decode($data);

        // Read the private key
        $privateKey = openssl_pkey_get_private(file_get_contents($this->privateKeyPath));
        if ($privateKey === false) {
            throw new Exception('Could not read private key');
        }

        // Decrypt the data
        $decryptedData = '';
        if (!openssl_open($data, $decryptedData, $envKey, $privateKey, 'AES256')) {
            throw new Exception('Could not decrypt data');
        }

        // Free the key
        openssl_free_key($privateKey);

        // Return the decrypted data
        return $decryptedData;
    }

    /**
     * Process the payment response
     *
     * @param string $envKey
     * @param string $data
     * @return Response
     * @throws Exception
     */
    public function processResponse(string $envKey, string $data)
    {
        // Decrypt the data
        $decryptedData = $this->decrypt($envKey, $data);

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
