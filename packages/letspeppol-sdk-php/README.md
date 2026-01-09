# LetsPeppol PHP SDK

Official PHP SDK for the LetsPeppol e-invoicing API, providing a simple and intuitive interface for integrating Peppol e-invoicing into your PHP applications.

## About LetsPeppol

[LetsPeppol](https://letspeppol.org) is a free, open-source platform for Peppol e-invoicing. The platform provides three main API modules:

- **KYC API** - Authentication, registration, and identity verification
- **Proxy API** - Document transmission and registry management
- **App API** - Document management, partners, products, and business logic

## Installation

### For Local Development (Path Repository)

Add the following to your project's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "./packages/letspeppol-sdk-php"
    }
  ],
  "require": {
    "letspeppol/letspeppol-sdk-php": "*"
  }
}
```

Then run:

```bash
composer install
```

### For Production (Packagist)

Once published to Packagist, you can install directly:

```bash
composer require letspeppol/letspeppol-sdk-php
```

## Requirements

- PHP 8.1 or higher
- Guzzle HTTP client 7.4.5 or higher

## Quick Start

```php
use LetsPeppolSdk\LetsPeppolClient;

// Create a new client instance
$client = new LetsPeppolClient();

// Authenticate and get JWT token
$token = $client->authenticate('user@example.com', 'password123');

// Get company information
$company = $client->app()->getCompany();

// List documents
$documents = $client->app()->listDocuments([
    'type' => 'INVOICE',
    'direction' => 'INCOMING'
]);

// Get new documents from proxy
$newDocs = $client->proxy()->getAllNewDocuments();
```

## Usage Examples

### Authentication

```php
$client = new LetsPeppolClient();

// Authenticate
$token = $client->authenticate('user@example.com', 'password');

// Or create client with existing token
$client = LetsPeppolClient::withToken($existingToken);

// Get account information
$account = $client->kyc()->getAccountInfo();
```

### Document Management

```php
// Validate UBL XML
$validation = $client->app()->validateDocument($ublXmlString);

if ($validation['valid']) {
    // Create document as draft
    $document = $client->app()->createDocument($ublXmlString, true);
    
    // Send when ready
    $sent = $client->app()->sendDocument($document['id']);
}

// List documents with filters
$documents = $client->app()->listDocuments([
    'type' => 'INVOICE',
    'direction' => 'OUTGOING',
    'draft' => false
], 0, 20);

// Get specific document
$document = $client->app()->getDocument($documentId);

// Mark as read
$client->app()->markDocumentRead($documentId);

// Mark as paid
$client->app()->markDocumentPaid($documentId);
```

### Document Transmission (Proxy)

```php
// Get new received documents
$newDocs = $client->proxy()->getAllNewDocuments(100);

foreach ($newDocs as $doc) {
    // Process document
    processDocument($doc);
    
    // Mark as downloaded
    $client->proxy()->markDownloaded($doc['id']);
}

// Create document to send
$document = $client->proxy()->createDocument([
    'ownerPeppolId' => '0208:BE0123456789',
    'counterPartyPeppolId' => '0208:BE0987654321',
    'ubl' => $ublXmlString,
    'direction' => 'OUTGOING',
    'documentType' => 'INVOICE'
]);

// Get status updates
$updates = $client->proxy()->getStatusUpdates([
    $docId1,
    $docId2,
    $docId3
]);
```

### Partner Management

```php
// List partners
$partners = $client->app()->listPartners();

// Search for a partner
$results = $client->app()->searchPartners('0208:BE0987654321');

// Add new partner
$partner = $client->app()->createPartner([
    'peppolId' => '0208:BE0987654321',
    'name' => 'Partner Company',
    'vatNumber' => 'BE0987654321',
    'email' => 'contact@partner.com'
]);

// Update partner
$updated = $client->app()->updatePartner($partnerId, [
    'name' => 'Updated Partner Name'
]);

// Delete partner
$client->app()->deletePartner($partnerId);
```

### Product Management

```php
// List products
$products = $client->app()->listProducts();

// Create product
$product = $client->app()->createProduct([
    'name' => 'Product Name',
    'description' => 'Product Description',
    'price' => 99.99,
    'unit' => 'piece',
    'sku' => 'PROD-001'
]);

// Update product
$updated = $client->app()->updateProduct($productId, [
    'price' => 89.99
]);

// Delete product
$client->app()->deleteProduct($productId);
```

### Registration Flow

```php
// Step 1: Get company information
$company = $client->kyc()->getCompany('0208:BE0123456789');

// Step 2: Confirm company and send verification email
$result = $client->kyc()->confirmCompany([
    'peppolId' => '0208:BE0123456789',
    'email' => 'admin@company.com',
    'name' => 'John Doe',
    'password' => 'securePassword123'
], 'en');

// Step 3: Verify email token
$verification = $client->kyc()->verifyToken($tokenFromEmail);

// Step 4: Prepare signing (requires Web eID)
$prepare = $client->kyc()->prepareSigning([
    'token' => $tokenFromEmail,
    'directorId' => $directorId,
    'certificate' => $base64Certificate
]);

// Step 5: Get contract PDF
$contractPdf = $client->kyc()->getContract($directorId, $tokenFromEmail);

// Step 6: Finalize signing
$result = $client->kyc()->finalizeSigning([
    'token' => $tokenFromEmail,
    'directorId' => $directorId,
    'signature' => $base64Signature
]);
```

## Error Handling

All API methods throw exceptions on failure:

```php
use LetsPeppolSdk\Exceptions\ApiException;
use LetsPeppolSdk\Exceptions\AuthenticationException;

try {
    $company = $client->app()->getCompany();
} catch (AuthenticationException $e) {
    // Handle authentication errors
    echo "Authentication failed: " . $e->getMessage();
} catch (ApiException $e) {
    $statusCode = $e->getCode();
    $message = $e->getMessage();
    $responseData = $e->getResponseData();
    
    // Handle API errors
    if ($statusCode === 404) {
        echo "Resource not found";
    } elseif ($statusCode === 401) {
        echo "Token expired - re-authenticate";
    }
}
```

## Custom Base URLs

You can specify custom base URLs for each API module:

```php
$client = new LetsPeppolClient(
    'https://custom-kyc.example.com',
    'https://custom-proxy.example.com',
    'https://custom-app.example.com'
);
```

## API Documentation

For complete API documentation and all available methods, see:
- [Full API Documentation](../../docs/api/LETSPEPPOL.md)
- [Quick Start Guide](../../docs/api/LETSPEPPOL-QUICKSTART.md)
- [OpenAPI Specifications](../../storage/api-specs/)

## Features

- ✅ Complete coverage of all LetsPeppol API endpoints
- ✅ Authentication and registration flow
- ✅ Document management (create, read, update, delete)
- ✅ Document validation and transmission
- ✅ Partner and product management
- ✅ Peppol Directory integration
- ✅ Registry management
- ✅ Type-safe method signatures
- ✅ Comprehensive error handling
- ✅ Framework-agnostic (works with any PHP application)

## Testing

Run the test suite:

```bash
composer test
```

Run code style checks:

```bash
composer cs-check
```

Fix code style issues:

```bash
composer cs-fix
```

## Resources

- [LetsPeppol Official Website](https://letspeppol.org)
- [LetsPeppol GitHub Repository](https://github.com/letspeppol/letspeppol)
- [Peppol Network Information](https://peppol.org)

## License

This SDK is open-source software licensed under the MIT license.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues related to:
- This SDK: Open an issue on GitHub
- LetsPeppol platform: Visit [letspeppol.org](https://letspeppol.org) or the [official repository](https://github.com/letspeppol/letspeppol)
