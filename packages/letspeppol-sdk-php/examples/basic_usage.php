<?php

require_once __DIR__ . '/../vendor/autoload.php';

use LetsPeppolSdk\LetsPeppolClient;
use LetsPeppolSdk\Exceptions\AuthenticationException;
use LetsPeppolSdk\Exceptions\ApiException;

// Basic authentication example
function authenticateExample()
{
    $client = new LetsPeppolClient();
    
    try {
        // Authenticate and get JWT token
        $token = $client->authenticate('user@example.com', 'password123');
        echo "Authentication successful! Token: " . substr($token, 0, 20) . "...\n";
        
        // Get account info
        $account = $client->kyc()->getAccountInfo();
        echo "Account: {$account['companyName']}\n";
        
    } catch (AuthenticationException $e) {
        echo "Authentication failed: {$e->getMessage()}\n";
    } catch (ApiException $e) {
        echo "API error: {$e->getMessage()}\n";
    }
}

// Document management example
function documentManagementExample(LetsPeppolClient $client)
{
    try {
        // List incoming invoices
        $documents = $client->app()->listDocuments([
            'type' => 'INVOICE',
            'direction' => 'INCOMING',
            'read' => false
        ], 0, 10);
        
        echo "Found " . count($documents) . " unread invoices\n";
        
        foreach ($documents as $doc) {
            echo "- Invoice {$doc['id']}: {$doc['total']} {$doc['currency']}\n";
            
            // Mark as read
            $client->app()->markDocumentRead($doc['id']);
        }
        
    } catch (ApiException $e) {
        echo "Error listing documents: {$e->getMessage()}\n";
    }
}

// Partner search example
function partnerSearchExample(LetsPeppolClient $client)
{
    try {
        // Search for a partner by Peppol ID
        $partners = $client->app()->searchPartners('0208:BE0987654321');
        
        if (!empty($partners)) {
            $partner = $partners[0];
            echo "Found partner: {$partner['name']}\n";
        } else {
            echo "Partner not found\n";
        }
        
    } catch (ApiException $e) {
        echo "Error searching partners: {$e->getMessage()}\n";
    }
}

// Receive documents from proxy example
function receiveDocumentsExample(LetsPeppolClient $client)
{
    try {
        // Get new documents from proxy
        $newDocs = $client->proxy()->getAllNewDocuments(50);
        
        echo "Received " . count($newDocs) . " new documents\n";
        
        foreach ($newDocs as $doc) {
            echo "Processing document {$doc['id']}...\n";
            
            // Process the document (e.g., save to database)
            // processDocument($doc);
            
            // Mark as downloaded
            $client->proxy()->markDownloaded($doc['id']);
            echo "Document {$doc['id']} marked as downloaded\n";
        }
        
    } catch (ApiException $e) {
        echo "Error receiving documents: {$e->getMessage()}\n";
    }
}

// Send invoice example
function sendInvoiceExample(LetsPeppolClient $client, string $ublXml)
{
    try {
        // First validate the UBL XML
        $validation = $client->app()->validateDocument($ublXml);
        
        if (!$validation['valid']) {
            echo "Validation failed: " . json_encode($validation['errors']) . "\n";
            return;
        }
        
        echo "UBL validation successful\n";
        
        // Create as draft first
        $document = $client->app()->createDocument($ublXml, true);
        echo "Draft created with ID: {$document['id']}\n";
        
        // Send the document
        $sent = $client->app()->sendDocument($document['id']);
        echo "Document sent successfully! Status: {$sent['status']}\n";
        
    } catch (ApiException $e) {
        echo "Error sending invoice: {$e->getMessage()}\n";
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    echo "LetsPeppol SDK Examples\n";
    echo "======================\n\n";
    
    // Example 1: Authentication
    echo "Example 1: Authentication\n";
    authenticateExample();
    echo "\n";
    
    // For other examples, you need an authenticated client
    // Uncomment and modify with your credentials:
    /*
    $client = LetsPeppolClient::withToken('your-jwt-token-here');
    
    echo "Example 2: Document Management\n";
    documentManagementExample($client);
    echo "\n";
    
    echo "Example 3: Partner Search\n";
    partnerSearchExample($client);
    echo "\n";
    
    echo "Example 4: Receive Documents\n";
    receiveDocumentsExample($client);
    echo "\n";
    */
}
