<?php

/**
 * Example demonstrating the new logging feature
 * 
 * This example shows how to use the LetsPeppol SDK with request/response logging enabled.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LetsPeppolSdk\LetsPeppolClient;
use LetsPeppolSdk\Config;

// Example 1: Using the Config class (inspired by the peppyrus pattern)
echo "Example 1: Using Config class\n";
echo "==============================\n\n";

Config::$endpoint = 'https://api.letspeppol.org';
Config::$key = 'your-api-key-here';
Config::$log_file = '/tmp/letspeppol-sdk.log';

echo "Config set:\n";
echo "  - Endpoint: " . Config::$endpoint . "\n";
echo "  - API Key: " . (empty(Config::$key) ? 'not set' : '***') . "\n";
echo "  - Log File: " . Config::$log_file . "\n\n";

// Example 2: Creating a client with logging enabled
echo "Example 2: Client with logging enabled\n";
echo "======================================\n\n";

$logFile = '/tmp/letspeppol-requests.log';

// Create client with logging
$client = new LetsPeppolClient(
    kycUrl: 'https://kyc.letspeppol.org',
    proxyUrl: 'https://proxy.letspeppol.org',
    appUrl: 'https://app.letspeppol.org',
    token: null,
    logFile: $logFile
);

echo "Client created with logging enabled\n";
echo "Logs will be written to: $logFile\n\n";

// Example 3: Creating a client without logging
echo "Example 3: Client without logging\n";
echo "=================================\n\n";

$clientNoLog = new LetsPeppolClient();

echo "Client created without logging\n";
echo "HTTP requests will not be logged\n\n";

// Example 4: Using the withToken factory method with logging
echo "Example 4: Factory method with logging\n";
echo "======================================\n\n";

$token = 'YOUR_JWT_TOKEN_HERE';
$clientWithToken = LetsPeppolClient::withToken(
    token: $token,
    logFile: '/tmp/letspeppol-authenticated.log'
);

echo "Authenticated client created with logging\n";
echo "Token: " . substr($token, 0, 30) . "...\n\n";

// Example 5: Direct use of GuzzleClient
echo "Example 5: Direct GuzzleClient usage\n";
echo "====================================\n\n";

use LetsPeppolSdk\GuzzleClient;

// Without logging
$httpClient1 = new GuzzleClient([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'Content-Type' => 'application/json',
    ],
]);

echo "GuzzleClient created without logging\n";

// With logging
$httpClient2 = new GuzzleClient([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'Content-Type' => 'application/json',
    ],
], '/tmp/guzzle-requests.log');

echo "GuzzleClient created with logging to /tmp/guzzle-requests.log\n\n";

// Example 6: Error handling
echo "Example 6: Error handling\n";
echo "=========================\n\n";

echo "The GuzzleClient automatically handles:\n";
echo "  - 401 Unauthorized: Throws 'Authentication failure' exception\n";
echo "  - 500 Internal Server Error: Logs response body and throws exception\n";
echo "  - Other errors: Standard Guzzle error handling\n\n";

// Example 7: Log file format
echo "Example 7: Log file format\n";
echo "==========================\n\n";

echo "When logging is enabled, each request will be logged with:\n";
echo "  1. Request: {method} {uri} HTTP/{version} {req_body} - {req_headers}\n";
echo "  2. Response: RESPONSE: {code} - {res_body}\n\n";

echo "Example log entries:\n";
echo "---\n";
echo "POST /api/v1/authenticate HTTP/1.1 {\"email\":\"user@example.com\"} - {\"Content-Type\":\"application/json\"}\n";
echo "RESPONSE: 200 - {\"token\":\"eyJ...\"}\n";
echo "---\n\n";

echo "All examples completed!\n";
echo "\nNote: To actually make requests and see logs, you need:\n";
echo "  1. Valid API credentials\n";
echo "  2. Network access to LetsPeppol APIs\n";
echo "  3. Write permissions to the log file location\n";
