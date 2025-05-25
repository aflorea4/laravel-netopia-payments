<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Simple test for encryption/decryption without Laravel dependencies
class NetopiaEncryptionTest
{
    private $publicKeyPath;
    private $privateKeyPath;
    private $signature;

    public function __construct($signature, $publicKeyPath, $privateKeyPath)
    {
        $this->signature = $signature;
        $this->publicKeyPath = $publicKeyPath;
        $this->privateKeyPath = $privateKeyPath;
    }

    /**
     * Test the encryption with different cipher algorithms
     */
    public function testEncryption()
    {
        echo "Testing encryption with multiple cipher algorithms...\n";
        
        // Test data to encrypt
        $testData = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<order>\n<signature>{$this->signature}</signature>\n<amount>1.00</amount>\n<currency>RON</currency>\n</order>";
        
        // Try different ciphers
        $ciphers = ['RC4', 'AES-128-CBC', 'AES-256-CBC', 'BF-CBC'];
        $successfulCiphers = [];
        
        foreach ($ciphers as $cipher) {
            echo "\nTesting cipher: $cipher\n";
            
            if (!in_array($cipher, openssl_get_cipher_methods())) {
                echo "  - Cipher $cipher is not available in this PHP installation.\n";
                continue;
            }
            
            try {
                // Encrypt
                $encryptResult = $this->encrypt($testData, $cipher);
                echo "  - Encryption successful!\n";
                
                // Decrypt
                $decryptResult = $this->decrypt(
                    $encryptResult['env_key'], 
                    $encryptResult['data'], 
                    $cipher, 
                    $encryptResult['iv'] ?? null
                );
                
                // Verify
                if ($decryptResult === $testData) {
                    echo "  - Decryption successful! Data matches original.\n";
                    $successfulCiphers[] = $cipher;
                } else {
                    echo "  - Decryption failed! Data does not match original.\n";
                }
            } catch (Exception $e) {
                echo "  - Error: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\nSummary:\n";
        if (count($successfulCiphers) > 0) {
            echo "The following ciphers worked successfully: " . implode(', ', $successfulCiphers) . "\n";
            echo "Recommended cipher to use: " . $successfulCiphers[0] . "\n";
        } else {
            echo "No ciphers worked successfully. Please check your certificates and PHP configuration.\n";
        }
    }
    
    /**
     * Encrypt data using the public key
     */
    protected function encrypt($data, $cipher)
    {
        // Read the public key
        $publicKey = openssl_pkey_get_public(file_get_contents($this->publicKeyPath));
        if ($publicKey === false) {
            throw new Exception('Could not read public key');
        }

        // Encrypt the data
        $encryptedData = '';
        $envKeys = [];
        
        // Generate IV if needed
        $iv = null;
        if ($cipher !== 'RC4') {
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
        }
        
        // Try to encrypt with this cipher
        if (!openssl_seal($data, $encryptedData, $envKeys, [$publicKey], $cipher, $iv)) {
            throw new Exception('Could not encrypt data with cipher: ' . $cipher);
        }

        // Free the key
        openssl_free_key($publicKey);

        // Return the encrypted data
        $result = [
            'env_key' => base64_encode($envKeys[0]),
            'data' => base64_encode($encryptedData),
            'cipher' => $cipher,
        ];
        
        // Add IV if used
        if ($iv !== null && $cipher !== 'RC4') {
            $result['iv'] = base64_encode($iv);
        }
        
        return $result;
    }
    
    /**
     * Decrypt data using the private key
     */
    protected function decrypt($envKey, $data, $cipher, $iv = null)
    {
        // Decode the data
        $envKey = base64_decode($envKey);
        $data = base64_decode($data);
        if ($iv !== null) {
            $iv = base64_decode($iv);
        }

        // Read the private key
        $privateKey = openssl_pkey_get_private(file_get_contents($this->privateKeyPath));
        if ($privateKey === false) {
            throw new Exception('Could not read private key');
        }

        // Decrypt the data
        $decryptedData = '';
        if (!openssl_open($data, $decryptedData, $envKey, $privateKey, $cipher, $iv)) {
            throw new Exception('Could not decrypt data with cipher: ' . $cipher);
        }

        // Free the key
        openssl_free_key($privateKey);

        // Return the decrypted data
        return $decryptedData;
    }
}

// Configuration
$signature = 'NETOPIA';
$publicKeyPath = __DIR__ . '/certs/public.cer';
$privateKeyPath = __DIR__ . '/certs/private.key';

// Check if certificate files exist
if (!file_exists($publicKeyPath) || !file_exists($privateKeyPath)) {
    echo "Error: Certificate files not found.\n";
    echo "Please make sure the following files exist:\n";
    echo "- $publicKeyPath\n";
    echo "- $privateKeyPath\n";
    exit(1);
}

// Run the test
echo "Netopia Payments Encryption Test\n";
echo "-------------------------------\n\n";

$test = new NetopiaEncryptionTest($signature, $publicKeyPath, $privateKeyPath);
$test->testEncryption();
