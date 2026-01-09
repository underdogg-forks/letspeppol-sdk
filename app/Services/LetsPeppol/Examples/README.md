# LetsPeppol API Client Examples

This directory contains practical examples demonstrating how to use the LetsPeppol API client.

## ExampleUsage.php

The `ExampleUsage` class provides 12 comprehensive examples covering:

1. **Authentication** - How to authenticate and get JWT tokens
2. **Registration Flow** - Complete user registration process
3. **Document Management** - Create, validate, and send documents
4. **Receive Documents** - Process incoming documents
5. **Partner Management** - Manage business partners
6. **Product Catalog** - Create and manage products
7. **Company Information** - Get and update company data
8. **Statistics** - Retrieve analytics and totals
9. **Peppol Directory** - Search the Peppol directory
10. **Error Handling** - Proper exception handling
11. **Token Management** - Work with existing tokens
12. **Batch Operations** - Process multiple items efficiently

## How to Use

These examples are for reference only and demonstrate the API patterns. Do not run them directly in production.

To use them in your code:

```php
use App\Services\LetsPeppol\LetsPeppolClient;

// Create a client instance
$client = new LetsPeppolClient();

// Authenticate
$token = $client->authenticate('user@example.com', 'password');

// Use any of the API methods
$company = $client->app()->getCompany();
```

## See Also

- [Full API Documentation](../../../docs/api/LETSPEPPOL.md)
- [Quick Start Guide](../../../docs/api/LETSPEPPOL-QUICKSTART.md)
- [OpenAPI Specifications](../../../storage/api-specs/)
