<?php

namespace Tests;

class TestHelper
{
    /**
     * Get the Netopia signature from environment or use a test value
     *
     * @return string
     */
    public static function getTestSignature()
    {
        return env('NETOPIA_TEST_SIGNATURE', 'TEST-SIGNATURE-FOR-UNIT-TESTS');
    }
    
    /**
     * Get the path to the test public certificate
     * Creates a mock certificate if it doesn't exist
     *
     * @return string
     */
    public static function getTestPublicKeyPath()
    {
        $certPath = __DIR__ . '/certs/public.cer';
        self::ensureTestCertificatesExist();
        return $certPath;
    }
    
    /**
     * Get the path to the test private key
     * Creates a mock key if it doesn't exist
     *
     * @return string
     */
    public static function getTestPrivateKeyPath()
    {
        $keyPath = __DIR__ . '/certs/private.key';
        self::ensureTestCertificatesExist();
        return $keyPath;
    }
    
    /**
     * Ensure test certificates exist, create them if they don't
     * This is particularly important for CI/CD environments
     *
     * @return void
     */
    private static function ensureTestCertificatesExist()
    {
        $certsDir = __DIR__ . '/certs';
        $publicCert = $certsDir . '/public.cer';
        $privateKey = $certsDir . '/private.key';
        
        // Create directory if it doesn't exist
        if (!is_dir($certsDir)) {
            mkdir($certsDir, 0755, true);
        }
        
        // Create private key if it doesn't exist
        if (!file_exists($privateKey)) {
            // Create a mock private key for testing
            $privateKeyContent = <<<EOT
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAvgm4DCU6BqKxmg0bWVsOLm+PAOAZyJGTnqmWPqYQHOiDCYBm
FxHi7JdwW+09QmhU4yJFABP+CWnQPOkDu1J5YZyNLXXTKu3HxbGhpDmXgcTBgptz
rJKTr1VjYGRJ0LL5/X/BcXCFa1CJXrOC4jLnMIGsNAGMCx9JgRyecggrG/FoELg7
CQQM4X4vTYoKd3/QihP5MzQQCh/cNv/Z5zFvRuBGwJG1hI1CcGYFNAEUf0GCfxnk
rONuzGF8FSKdkTrwLgVNE8YECpEDN1zRCDZ+/jLuQsJT1ER5Z//3ZgNuJiOTMZZL
kKbjWT3oWLKdMPvONJxANpz1JLu0VvYQQTi5QwIDAQABAoIBAGmCVjvMQRX7nNcK
fKCLnW33LzGvWTmdBQHEiF7nFJWOYdBvG7u6sTvMqxoHJUbPTacBQhJYtlQyj9FA
XVyWyH1FrfQEEKFtGNoUDYcBZ+hJYLYDcJLQrSBBuuLCJ0RNZ5S8FU1+QgVLW/5Z
rJUm8UPq5V9IWZKD0l8fUL5h6/l6qI7XIgGUTtR3nQRoIrBkjsTWOZFYjmJKGEQz
DnxoYnZPOdQpI5OQlYKyVEW/QM8wBXN9Qoe7kqIq5K6JIFXoMNQoXG5R8oGzYBQl
ZKYMkOOcJy4x1ZFQQsRQiYM/IqvK8gGnD9QYzSaxzj8YPQU7g9wEneQVgHdmp6Sm
R9YG5YECgYEA+Qk6UEjdvmK8LLrMpYwFIWPKP/5GV9Qc+J89PN6t9LGg5ydXVyWK
YKFYu2EMgzH98TdcVxhYYBTSBUEJQWXHYkxMsGpKcZgHLGvDSxRs2wuCKQZpAyoi
CjLdlL/LJAQvnFkVCKgWZ5IqCUxeRHLlPRU5/XtLUPvPBZy0Ys0XP0MCgYEAw4OA
OVKQcnwRvWnmB8yfgRSLRa+7dVnFc5VXW5SkOXXvVnNJXE/rQ18hF8jgS4lqwFpT
QIXXRVIpXKxK6qNnNrCzVKzXgcR/ET2zCaX0jVyZaQ7Jth5ijCuKrNIUYyDyRlzb
JY7+HdIRMuLLmGPTVjnANYqJEQEJcT+2/ZMsRiECgYEA8LsxNuGOGu4RWxMJH6WH
chWKVpOQjHQYQOEbCJg0GCYx9DBaCMKQDpKV5fGpJxfHYGF+Qy9VWEGTr8TwUiQZ
BDsC+oLqrH7IwgjFVxBwOYNS7xaQeKkzxFYoXRQxdz1XIJmzRXBh5F+WuaVNWFAJ
WQp9FGldbFUloqXFAQnIXAMCgYBqIjKZVJwPJR3m/QUKKJnFGwhXLwWpvG1T9h9N
QO9lq8kUXzJ7C2VDcRpvd8VkBmKrM5gJY9m/hPBvQ9wzF7zGvHuXgZQDQKYBzUVl
FBVJDKRrGh4iK8V3ZrUDVVpzJRQCBBU7+mBR2gjyPNgfDxRAR0UzUVbZSRKj8JvL
xNnbIQKBgHKvy7zyHqL0rG7yjawvVsRV9yqiQeX5dGYQTYnCUxvspGY0yfFgOGUh
kFHKLbBkA0WnDG4l1ZqLDLEkU3lImFbZvl9KHnXQVW8jOTZHdVd3UJJJGIKYx4JQ
QwgYZJOFrJwRYOKuaqLgSRbVTWVeVPZVGQzwQs+WGYCk6S0JJxZ4
-----END RSA PRIVATE KEY-----
EOT;
            file_put_contents($privateKey, $privateKeyContent);
        }
        
        // Create public certificate if it doesn't exist
        if (!file_exists($publicCert)) {
            // Create a mock public certificate for testing
            $publicCertContent = <<<EOT
-----BEGIN CERTIFICATE-----
MIIDazCCAlOgAwIBAgIUEMGJYgOLmVGTLN+aHJvMvZdFXHUwDQYJKoZIhvcNAQEL
BQAwRTELMAkGA1UEBhMCVVMxEzARBgNVBAgMClNvbWUtU3RhdGUxITAfBgNVBAoM
GEludGVybmV0IFdpZGdpdHMgUHR5IEx0ZDAeFw0yMzA1MjYwMDAwMDBaFw0zMzA1
MjMwMDAwMDBaMEUxCzAJBgNVBAYTAlVTMRMwEQYDVQQIDApTb21lLVN0YXRlMSEw
HwYDVQQKDBhJbnRlcm5ldCBXaWRnaXRzIFB0eSBMdGQwggEiMA0GCSqGSIb3DQEB
AQUAA4IBDwAwggEKAoIBAQC+CbgMJToGorGaDRtZWw4ub48A4BnIkZOeqZY+phAc
6IMJgGYXEeLsl3Bb7T1CaFTjIkUAE/4JadA86QO7UnlhnI0tddMq7cfFsaGkOZeB
xMGCm3OskpOvVWNgZEnQsvn9f8FxcIVrUIles4LiMucwgaw0AYwLH0mBHJ5yCCsb
8WgQuDsJBAzhfi9Nigp3f9CKE/kzNBAKH9w2/9nnMW9G4EbAkbWEjUJwZgU0ARR/
QYJ/GeSs427MYXwVIp2ROvAuBU0TxgQKkQM3XNEINn7+Mu5CwlPURHln//dmA24m
I5MxlkuQpuNZPehYsp0w+840nEA2nPUku7RW9hBBOLlDAgMBAAGjUzBRMB0GA1Ud
DgQWBBQMRl7RD5kBRQUKF7dKLb0QE5iiKzAfBgNVHSMEGDAWgBQMRl7RD5kBRQUK
F7dKLb0QE5iiKzAPBgNVHRMBAf8EBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQB+
F/fqsWFrT+TTu1VTytW3IQYvV7UbQIcJ/dCJKzJYKGbGhV5LWJ4cM4iBZ9SSt2oc
OEZQN4Hx1nKWlxGPFJUzUQYRVGGt2MpF8wzMe0STnqP1MmQnbVwXpJXHVT4m4wXF
FdSGEpbFwSrBlzkWEHgQkZBkGeyOYQhTGQV0X0qOCPCj9CfM5JC/6VxQkKQhtZZd
kBppVwYkLRZwHZ5KXXJmVgpCQ+JrNEEVUu3SoJJ7m5ZQe0QO9bGqnCl1gKZkKYGQ
QhyG+cCMJ/RVA4FAiPHQVpL1L+J+skZZqXZHh+zfYPKgXs1+WCLdI+Qgz6l1vPQZ
YrxZbQF4QkJXTDPOZDuY
-----END CERTIFICATE-----
EOT;
            file_put_contents($publicCert, $publicCertContent);
        }
    }
}
