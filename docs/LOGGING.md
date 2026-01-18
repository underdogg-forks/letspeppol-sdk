# HTTP Request/Response Logging

This document describes the HTTP request/response logging feature added to the LetsPeppol SDK.

## Overview

The SDK now includes built-in support for logging all HTTP requests and responses using Monolog. This is useful for:
- Debugging API interactions
- Monitoring production traffic
- Auditing API usage
- Troubleshooting integration issues

## Features

### 1. Custom GuzzleClient

A new `GuzzleClient` class extends `GuzzleHttp\Client` with:
- **Conditional logging**: Logs only when a log file path is provided
- **Request logging**: Logs HTTP method, URI, version, body, and headers
- **Response logging**: Logs status code and response body
- **Error handling**: Automatic handling of 401 and 500 status codes
- **Monolog integration**: Uses Monolog for flexible, structured logging

### 2. Config Class

A new `Config` class provides global configuration:
```php
use LetsPeppolSdk\Config;

Config::$endpoint = 'https://api.letspeppol.org';
Config::$key = 'your-api-key';
Config::$log_file = '/var/log/letspeppol.log';
```

### 3. Session Updates

The `Session` class now supports:
- Log file configuration via constructor
- `getLogFile()` and `setLogFile()` methods
- Automatic client recreation when log file changes

### 4. LetsPeppolClient Updates

The main client now accepts a `$logFile` parameter:
```php
// With logging
$client = new LetsPeppolClient(
    token: 'your-token',
    logFile: '/var/log/letspeppol.log'
);

// Factory method with logging
$client = LetsPeppolClient::withToken(
    token: 'your-token',
    logFile: '/var/log/letspeppol.log'
);
```

## Usage Examples

### Basic Usage with Logging

```php
use LetsPeppolSdk\LetsPeppolClient;

// Enable logging for all requests
$client = new LetsPeppolClient(
    kycUrl: 'https://kyc.letspeppol.org',
    proxyUrl: 'https://proxy.letspeppol.org',
    appUrl: 'https://app.letspeppol.org',
    token: null,
    logFile: '/tmp/letspeppol-requests.log'
);

// Make requests - they will be logged automatically
$token = $client->authenticate('user@example.com', 'password');
$account = $client->kyc()->getAccountInfo();
```

### Without Logging

```php
// Omit the logFile parameter to disable logging
$client = new LetsPeppolClient();

// Or explicitly set to null
$client = new LetsPeppolClient(
    logFile: null
);
```

### Dynamic Logging Control

```php
$client = new LetsPeppolClient();

// Enable logging later
$session = $client->kyc()->getSession();
$session->setLogFile('/tmp/debug.log');

// Disable logging
$session->setLogFile(null);
```

### Direct GuzzleClient Usage

```php
use LetsPeppolSdk\GuzzleClient;

// Without logging
$httpClient = new GuzzleClient([
    'base_uri' => 'https://api.example.com',
    'headers' => ['Content-Type' => 'application/json'],
]);

// With logging
$httpClient = new GuzzleClient([
    'base_uri' => 'https://api.example.com',
    'headers' => ['Content-Type' => 'application/json'],
], '/var/log/http-requests.log');

// Make requests
$response = $httpClient->request('GET', '/endpoint');
```

## Log Format

When logging is enabled, each request generates two log entries:

**Request format:**
```
{method} {uri} HTTP/{version} {req_body} - {req_headers}
```

**Response format:**
```
RESPONSE: {code} - {res_body}
```

**Example:**
```
POST /api/v1/authenticate HTTP/1.1 {"email":"user@example.com","password":"super-secret-password"} - {"Content-Type":"application/json","User-Agent":"LetsPeppol PHP SDK"}
RESPONSE: 200 - {"token":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...","expiresIn":3600}
```

**Note:** The example above shows that sensitive data like passwords and tokens are logged as-is. Implement application-level sanitization if needed.

## Error Handling

The `GuzzleClient` automatically handles specific HTTP status codes:

### 401 Unauthorized
Throws an exception with message: `"Authentication failure"`

```php
try {
    $response = $client->request('GET', '/protected-endpoint');
} catch (\Exception $e) {
    // $e->getMessage() === "Authentication failure"
}
```

### 500 Internal Server Error
Logs the response body (via Monolog logger or error_log) and throws an exception: `"Internal server error"`

```php
try {
    $response = $client->request('POST', '/endpoint');
} catch (\Exception $e) {
    // Response body was logged via the configured logger or error_log()
    // $e->getMessage() === "Internal server error"
}
```

## Configuration

### Log File Location

Choose an appropriate location based on your environment:

**Development:**
```php
$logFile = '/tmp/letspeppol-dev.log';
```

**Production (Linux):**
```php
$logFile = '/var/log/letspeppol/requests.log';
```

**Production (with rotation):**
```php
// Monolog will handle the file writing
// You can set up log rotation externally using logrotate
$logFile = '/var/log/letspeppol/requests.log';
```

### Log Levels

Monolog is used internally with default configuration. The logs are written as INFO level messages.

### Performance Considerations

- Logging has minimal performance impact for typical API usage
- For high-traffic applications, consider:
  - Using asynchronous log handlers
  - Implementing log sampling (log only a percentage of requests)
  - Using faster storage for log files (SSD)
  - Implementing log rotation to prevent large files

## Security Considerations

**Important:** Log files may contain sensitive information:
- API tokens and credentials
- Personal data (PII)
- Business-sensitive information

**Best practices:**
1. Ensure log files have appropriate permissions (e.g., 600 or 640)
2. Store logs in a secure location
3. Implement log rotation and retention policies
4. Consider filtering sensitive data from logs
5. Comply with data protection regulations (GDPR, etc.)

## Troubleshooting

### Logs Not Being Created

1. Check file permissions:
   ```bash
   ls -la /var/log/letspeppol/
   ```

2. Ensure directory exists:
   ```bash
   mkdir -p /var/log/letspeppol
   ```

3. Verify PHP has write permissions:
   ```bash
   touch /var/log/letspeppol/test.log
   ```

### Logs Empty

1. Verify logging is enabled:
   ```php
   $logFile = $session->getLogFile();
   echo "Log file: " . ($logFile ?? 'not set');
   ```

2. Check if requests are actually being made

3. Verify Monolog is installed:
   ```bash
   composer show monolog/monolog
   ```

## Migration from Previous Versions

If you're upgrading from a previous version without logging:

```php
// Old code (still works)
$client = new LetsPeppolClient();

// New code with logging
$client = new LetsPeppolClient(
    logFile: '/var/log/letspeppol.log'
);
```

All existing code continues to work without changes. Logging is opt-in.

## See Also

- [Monolog Documentation](https://github.com/Seldaek/monolog)
- [Guzzle Documentation](https://docs.guzzlephp.org/)
- [Basic Usage Example](basic_usage.php)
- [Logging Example](logging_example.php)
