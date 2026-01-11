# SDK Error Handling & Documentation Guide

## Overview

This guide explains the robust error handling and comprehensive documentation added to the LetsPeppol PHP SDK.

## Error Handling Improvements

### 1. Intelligent Error Message Extraction

The SDK now intelligently extracts error messages from API responses:

```php
try {
    $company = $client->app()->getCompany();
} catch (ApiException $e) {
    echo $e->getMessage(); // Human-readable error message
}
```

The SDK checks multiple common error fields:
- `message`
- `error`
- `error_description`
- `detail`
- `title`

### 2. HTTP Status Code Categorization

Errors are categorized by HTTP status code with descriptive prefixes:

- **400**: Bad Request
- **401**: Unauthorized
- **403**: Forbidden  
- **404**: Not Found
- **409**: Conflict
- **422**: Validation Error
- **429**: Rate Limit Exceeded
- **5xx**: Server Error

### 3. Network Error Detection

Guzzle exceptions are categorized:

- **Connection Error**: Network connectivity issues
- **Request Error**: Malformed requests
- **Server Error**: Server-side failures
- **Client Error**: Client-side errors
- **Too Many Redirects**: Redirect loop detection

### 4. Enhanced Exception Methods

New helper methods on `LetsPeppolException`:

```php
try {
    $result = $client->kyc()->authenticate($email, $password);
} catch (LetsPeppolException $e) {
    // Check error type
    if ($e->isNetworkError()) {
        echo "Network connectivity issue";
    } elseif ($e->isClientError()) {
        echo "Invalid request (4xx)";
    } elseif ($e->isServerError()) {
        echo "Server error (5xx)";
    }
    
    // Get comprehensive error report
    $report = $e->getErrorReport();
    print_r($report);
    
    // Access HTTP status code
    $statusCode = $e->getStatusCode();
    
    // Get response data
    $responseData = $e->getResponseData();
}
```

### 5. JSON Decode Error Detection

The SDK detects and reports JSON parsing errors:

```php
// If API returns invalid JSON
try {
    $result = $client->app()->getDocument('doc123');
} catch (ApiException $e) {
    // Error message will include: "Invalid JSON response: ..."
    $responseData = $e->getResponseData();
    // Contains: ['body' => '...', 'json_error' => '...']
}
```

## Documentation Structure

Every public method now includes:

### 1. Method Description

Clear description of what the method does:

```php
/**
 * Authenticate and get JWT token
 *
 * Makes a POST request to /api/jwt/auth with Basic authentication.
 */
```

### 2. Request Documentation

Details about the HTTP request:

```php
/**
 * **Request:**
 * - Headers: Authorization: Basic base64(email:password)
 * - No body
 */
```

### 3. Response Documentation

JSON schemas for responses:

```php
/**
 * **Response JSON:**
 * ```json
 * {
 *   "peppolId": "0208:BE0123456789",
 *   "vatNumber": "BE0123456789",
 *   "name": "Company Name BVBA",
 *   "address": {
 *     "street": "Street Name 123",
 *     "city": "Brussels",
 *     "postalCode": "1000",
 *     "country": "BE"
 *   }
 * }
 * ```
 */
```

### 4. Usage Examples

Real-world code examples:

```php
/**
 * **Example:**
 * ```php
 * $company = $client->kyc()->getCompany('0208:BE0123456789');
 * echo "Company: " . $company['name'];
 * echo "VAT: " . $company['vatNumber'];
 * ```
 */
```

### 5. Error Scenarios

Documents when exceptions are thrown:

```php
/**
 * @throws ApiException When company not found (404) or invalid Peppol ID format
 */
```

## Example Usage Patterns

### Authentication with Error Handling

```php
use LetsPeppolSdk\LetsPeppolClient;
use LetsPeppolSdk\Exceptions\AuthenticationException;

try {
    $client = new LetsPeppolClient();
    $token = $client->authenticate('user@example.com', 'password');
    echo "Authenticated successfully";
} catch (AuthenticationException $e) {
    if ($e->getStatusCode() === 401) {
        echo "Invalid credentials";
    } elseif ($e->isNetworkError()) {
        echo "Network error: " . $e->getMessage();
    }
}
```

### Document Validation with Error Details

```php
use LetsPeppolSdk\Exceptions\ApiException;

try {
    $validation = $client->app()->validateDocument($ublXml);
    
    if (!$validation['valid']) {
        echo "Validation errors:\n";
        foreach ($validation['errors'] as $error) {
            echo "- $error\n";
        }
    }
} catch (ApiException $e) {
    if ($e->getStatusCode() === 400) {
        echo "Malformed UBL XML";
        $responseData = $e->getResponseData();
        print_r($responseData);
    }
}
```

### Handling Rate Limits

```php
try {
    $documents = $client->app()->listDocuments();
} catch (ApiException $e) {
    if ($e->getStatusCode() === 429) {
        echo "Rate limit exceeded, wait and retry";
        // Implement exponential backoff
    }
}
```

### Network Error Recovery

```php
$maxRetries = 3;
$retryCount = 0;

while ($retryCount < $maxRetries) {
    try {
        $newDocs = $client->proxy()->getAllNewDocuments();
        break; // Success
    } catch (ApiException $e) {
        if ($e->isNetworkError()) {
            $retryCount++;
            if ($retryCount >= $maxRetries) {
                throw $e; // All retries exhausted
            }
            sleep(pow(2, $retryCount)); // Exponential backoff
        } else {
            throw $e; // Not a network error, rethrow
        }
    }
}

## Comprehensive Error Report

Get detailed error information for debugging:

```php
try {
    $result = $client->app()->sendDocument('doc123');
} catch (LetsPeppolException $e) {
    $report = $e->getErrorReport();
    /*
    Array
    (
        [message] => Validation Error: 422 - Document not ready to send
        [code] => 422
        [endpoint] => /sapi/document/doc123/send
        [method] => PUT
        [is_network_error] => false
        [is_client_error] => true
        [is_server_error] => false
        [response_data] => Array
            (
                [errors] => Array
                    (
                        [0] => Document is in draft status
                    )
            )
        [previous_exception] => null
    )
    */
    
    // Log to monitoring system
    error_log(json_encode($report));
}
```

## Benefits

1. **Better Debugging**: Detailed error information helps identify issues quickly
2. **Clear API Contract**: JSON schemas show exactly what to send/expect
3. **Reduced Integration Time**: Examples and documentation reduce trial-and-error
4. **Robust Error Recovery**: Categorized errors enable intelligent retry logic
5. **Production Ready**: Comprehensive error handling for reliability

## Further Reading

- See method docblocks in source code for complete documentation
- Check `examples/` directory for full usage examples
- Review `COMPARISON.md` for before/after details
