<?php

require_once __DIR__ . '/../vendor/autoload.php';

use LetsPeppolSdk\Exceptions\ApiException;
use LetsPeppolSdk\LetsPeppolClient;

/**
 * Complete workflow example: From authentication to sending an invoice
 */

// Configuration
// WARNING: Do NOT hardcode credentials in production code!
// Use environment variables or a secure secrets manager instead:
// $email = getenv('LETSPEPPOL_EMAIL') ?: $_ENV['LETSPEPPOL_EMAIL'];
// $password = getenv('LETSPEPPOL_PASSWORD') ?: $_ENV['LETSPEPPOL_PASSWORD'];
$email = 'your-email@example.com';
$password = 'your-password';

// Sample UBL XML (simplified for demonstration)
$sampleUblXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
    <ID>INV-2024-001</ID>
    <IssueDate>2024-01-09</IssueDate>
    <InvoiceTypeCode>380</InvoiceTypeCode>
    <!-- Add more UBL elements as per specification -->
</Invoice>
XML;

try {
    echo "===========================================\n";
    echo "LetsPeppol SDK - Complete Workflow Example\n";
    echo "===========================================\n\n";

    // Step 1: Create client and authenticate
    echo "Step 1: Authenticating...\n";
    $client = new LetsPeppolClient();
    $token = $client->authenticate($email, $password);
    echo "* Authenticated successfully\n";
    echo "  Token (first 20 chars): " . substr($token, 0, 20) . "...\n\n";

    // Step 2: Get company information
    echo "Step 2: Retrieving company information...\n";
    $company = $client->app()->getCompany();
    echo "* Company: {$company['name']}\n";
    echo "  Peppol ID: {$company['peppolId']}\n\n";

    // Step 3: List existing partners
    echo "Step 3: Listing partners...\n";
    $partners = $client->app()->listPartners();
    echo "* Found " . count($partners) . " partner(s)\n";
    foreach ($partners as $partner) {
        echo "  - {$partner['name']} ({$partner['peppolId']})\n";
    }
    echo "\n";

    // Step 4: Search for a partner (or create if not exists)
    echo "Step 4: Managing partners...\n";
    $targetPeppolId = '0208:BE0987654321'; // Example
    $searchResults = $client->app()->searchPartners($targetPeppolId);

    if (empty($searchResults)) {
        echo "  Partner not found, creating...\n";
        $partner = $client->app()->createPartner([
            'peppolId'  => $targetPeppolId,
            'name'      => 'Example Partner Company',
            'vatNumber' => 'BE0987654321',
            'email'     => 'partner@example.com',
        ]);
        echo "* Partner created: {$partner['name']}\n\n";
    } else {
        echo "* Partner found: {$searchResults[0]['name']}\n\n";
    }

    // Step 5: Validate UBL XML
    echo "Step 5: Validating UBL XML...\n";
    $validation = $client->app()->validateDocument($sampleUblXml);

    if (isset($validation['valid']) && $validation['valid']) {
        echo "* UBL validation passed\n\n";
    } else {
        echo "* UBL validation failed:\n";
        if (isset($validation['errors'])) {
            foreach ($validation['errors'] as $error) {
                echo "  - $error\n";
            }
        }
        echo "\nSkipping document creation due to validation errors.\n";
        exit(1);
    }

    // Step 6: Create document as draft
    echo "Step 6: Creating document as draft...\n";
    $document = $client->app()->createDocument($sampleUblXml, true);
    echo "* Draft created\n";
    echo "  Document ID: {$document['id']}\n";
    echo "  Status: {$document['status']}\n\n";

    // Step 7: Review and send document
    echo "Step 7: Sending document...\n";
    $sent = $client->app()->sendDocument($document['id']);
    echo "* Document sent\n";
    echo "  New status: {$sent['status']}\n\n";

    // Step 8: Check for incoming documents
    echo "Step 8: Checking for incoming documents...\n";
    $newDocs = $client->proxy()->getAllNewDocuments(10);
    echo "* Found " . count($newDocs) . " new document(s)\n";

    if (! empty($newDocs)) {
        echo "  Processing new documents:\n";
        foreach ($newDocs as $doc) {
            echo "  - Document {$doc['id']}: {$doc['documentType']}\n";

            // Mark as downloaded after processing
            $client->proxy()->markDownloaded($doc['id']);
            echo "    * Marked as downloaded\n";
        }
    }
    echo "\n";

    // Step 9: Get statistics
    echo "Step 9: Retrieving account statistics...\n";
    $stats = $client->app()->getAccountTotals();
    echo "* Account totals:\n";
    if (isset($stats['incoming'])) {
        echo "  Incoming documents: {$stats['incoming']}\n";
    }
    if (isset($stats['outgoing'])) {
        echo "  Outgoing documents: {$stats['outgoing']}\n";
    }
    if (isset($stats['balance'])) {
        echo "  Balance: {$stats['balance']}\n";
    }
    echo "\n";

    echo "===========================================\n";
    echo "* Workflow completed successfully!\n";
    echo "===========================================\n";

} catch (ApiException $e) {
    echo "\n* Error occurred:\n";
    echo "  Code: {$e->getCode()}\n";
    echo "  Message: {$e->getMessage()}\n";

    $responseData = $e->getResponseData();
    if (! empty($responseData)) {
        echo "  Response data: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    }

    exit(1);
} catch (Exception $e) {
    echo "\n* Unexpected error:\n";
    echo "  " . $e->getMessage() . "\n";
    exit(1);
}
