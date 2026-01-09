<?php

namespace App\Services\LetsPeppol\Examples;

use App\Services\LetsPeppol\LetsPeppolClient;

/**
 * Example usage of LetsPeppol API Client
 * 
 * This file demonstrates common use cases for the LetsPeppol API.
 * It's meant for reference and should not be run directly in production.
 */
class ExampleUsage
{
    private LetsPeppolClient $client;

    public function __construct()
    {
        $this->client = new LetsPeppolClient();
    }

    /**
     * Example 1: Authentication
     */
    public function authenticateExample(): void
    {
        // Authenticate and get JWT token
        $token = $this->client->authenticate('user@example.com', 'password123');
        
        echo "Authenticated successfully!\n";
        echo "Token: {$token}\n";
        
        // Token is automatically set for all API clients
    }

    /**
     * Example 2: Complete Registration Flow
     */
    public function registrationFlowExample(): void
    {
        $peppolId = '0208:BE0123456789';
        
        // Step 1: Get company information
        $company = $this->client->kyc()->getCompany($peppolId);
        echo "Company: {$company['name']}\n";
        
        // Step 2: Confirm company and send verification email
        $result = $this->client->kyc()->confirmCompany([
            'peppolId' => $peppolId,
            'email' => 'admin@company.com',
            'name' => 'John Doe',
            'password' => 'securePassword123'
        ], 'en');
        echo "Verification email sent\n";
        
        // Step 3: After user clicks email link, verify token
        $tokenFromEmail = 'token-from-email-link';
        $verification = $this->client->kyc()->verifyToken($tokenFromEmail);
        echo "Email verified! Directors: " . count($verification['directors']) . "\n";
        
        // Steps 4-6 involve Web eID signing (requires Belgian eID card)
        // See documentation for full implementation
    }

    /**
     * Example 3: Document Management
     */
    public function documentManagementExample(): void
    {
        // Load UBL XML from file
        $ublXml = file_get_contents('path/to/invoice.xml');
        
        // Validate the document
        $validation = $this->client->app()->validateDocument($ublXml);
        
        if (!$validation['valid']) {
            echo "Validation errors:\n";
            print_r($validation['errors']);
            return;
        }
        
        // Create document as draft
        $document = $this->client->app()->createDocument($ublXml, true);
        echo "Document created: {$document['id']}\n";
        
        // Send the document
        $sent = $this->client->app()->sendDocument($document['id']);
        echo "Document sent!\n";
        
        // List all documents with filters
        $documents = $this->client->app()->listDocuments([
            'type' => 'INVOICE',
            'direction' => 'OUTGOING',
            'draft' => false
        ], 0, 20);
        
        echo "Found " . count($documents['content']) . " documents\n";
    }

    /**
     * Example 4: Receive and Process Documents
     */
    public function receiveDocumentsExample(): void
    {
        // Get new received documents from proxy
        $newDocs = $this->client->proxy()->getAllNewDocuments(100);
        
        echo "Received " . count($newDocs) . " new documents\n";
        
        foreach ($newDocs as $doc) {
            echo "\nDocument: {$doc['id']}\n";
            echo "Type: {$doc['documentType']}\n";
            echo "Amount: {$doc['amount']} {$doc['currency']}\n";
            echo "From: {$doc['counterPartyName']}\n";
            
            // Process the document (save to database, etc.)
            $this->processDocument($doc);
            
            // Mark as downloaded
            $this->client->proxy()->markDownloaded($doc['id']);
            echo "Marked as downloaded\n";
        }
    }

    /**
     * Example 5: Partner Management
     */
    public function partnerManagementExample(): void
    {
        // List all partners
        $partners = $this->client->app()->listPartners();
        echo "Total partners: " . count($partners) . "\n";
        
        // Search for a specific partner
        $searchResults = $this->client->app()->searchPartners('0208:BE0987654321');
        
        if (empty($searchResults)) {
            // Create new partner
            $partner = $this->client->app()->createPartner([
                'peppolId' => '0208:BE0987654321',
                'name' => 'Partner Company BVBA',
                'vatNumber' => 'BE0987654321',
                'email' => 'contact@partner.com',
                'street' => 'Partner Street 1',
                'city' => 'Brussels',
                'postalCode' => '1000',
                'country' => 'BE'
            ]);
            echo "Partner created: {$partner['id']}\n";
        } else {
            echo "Partner already exists\n";
        }
    }

    /**
     * Example 6: Product Catalog
     */
    public function productCatalogExample(): void
    {
        // Create a category
        $category = $this->client->app()->createCategory([
            'name' => 'Electronics',
            'parentId' => null
        ]);
        echo "Category created: {$category['id']}\n";
        
        // Create a product
        $product = $this->client->app()->createProduct([
            'name' => 'Laptop',
            'description' => 'High-performance laptop',
            'price' => 999.99,
            'unit' => 'piece',
            'sku' => 'LAPTOP-001',
            'categoryId' => $category['id']
        ]);
        echo "Product created: {$product['id']}\n";
        
        // List all products
        $products = $this->client->app()->listProducts();
        echo "Total products: " . count($products) . "\n";
    }

    /**
     * Example 7: Company Information
     */
    public function companyInfoExample(): void
    {
        // Get current company information
        $company = $this->client->app()->getCompany();
        
        echo "Company: {$company['name']}\n";
        echo "Peppol ID: {$company['peppolId']}\n";
        echo "VAT: {$company['vatNumber']}\n";
        
        // Update company information
        $updated = $this->client->app()->updateCompany([
            'peppolId' => $company['peppolId'],
            'name' => $company['name'],
            'email' => 'newemail@company.com',
            'phone' => '+32 2 123 45 67',
            'website' => 'https://company.com'
        ]);
        
        echo "Company updated\n";
    }

    /**
     * Example 8: Statistics and Analytics
     */
    public function statisticsExample(): void
    {
        // Get account totals
        $totals = $this->client->app()->getAccountTotals();
        
        echo "Statistics:\n";
        echo "Total Invoices: {$totals['totalInvoices']}\n";
        echo "Total Amount: {$totals['totalAmount']} {$totals['currency']}\n";
        echo "Total Paid: {$totals['totalPaid']} {$totals['currency']}\n";
        echo "Total Unpaid: {$totals['totalUnpaid']} {$totals['currency']}\n";
    }

    /**
     * Example 9: Peppol Directory Search
     */
    public function peppolDirectorySearchExample(): void
    {
        // Search by company name
        $results = $this->client->app()->searchPeppolDirectory('Microsoft', null);
        
        echo "Search results:\n";
        if (is_array($results)) {
            foreach ($results as $result) {
                if (isset($result['name'], $result['peppolId'])) {
                    echo "- {$result['name']} ({$result['peppolId']})\n";
                }
            }
        }
    }

    /**
     * Example 10: Error Handling
     */
    public function errorHandlingExample(): void
    {
        try {
            $document = $this->client->app()->getDocument('non-existent-id');
        } catch (\RuntimeException $e) {
            $statusCode = $e->getCode();
            $message = $e->getMessage();
            
            if ($statusCode === 401) {
                echo "Authentication failed - token expired\n";
                // Re-authenticate
                $this->client->authenticate('user@example.com', 'password');
            } elseif ($statusCode === 404) {
                echo "Document not found\n";
            } else {
                echo "API Error: {$message}\n";
            }
        }
    }

    /**
     * Helper method to process a document
     */
    private function processDocument(array $document): void
    {
        // Example processing:
        // - Save to database
        // - Send notification
        // - Update accounting system
        // etc.
        
        echo "Processing document {$document['id']}...\n";
    }

    /**
     * Example 11: Working with existing token
     */
    public function existingTokenExample(): void
    {
        // If you already have a token from previous session
        $existingToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...';
        
        // Create client with token
        $client = LetsPeppolClient::withToken($existingToken);
        
        // Now you can use all API methods
        $company = $client->app()->getCompany();
        echo "Company: {$company['name']}\n";
    }

    /**
     * Example 12: Batch Operations
     */
    public function batchOperationsExample(): void
    {
        // Get multiple document status updates at once
        $documentIds = [
            '123e4567-e89b-12d3-a456-426614174000',
            '123e4567-e89b-12d3-a456-426614174001',
            '123e4567-e89b-12d3-a456-426614174002',
        ];
        
        $updates = $this->client->proxy()->getStatusUpdates($documentIds);
        
        foreach ($updates as $doc) {
            echo "Document {$doc['id']}: {$doc['status']}\n";
        }
        
        // Mark multiple documents as downloaded
        $downloadedIds = array_filter($documentIds, function($id) {
            // Filter logic here
            return true;
        });
        
        $this->client->proxy()->markDownloadedBatch($downloadedIds);
        echo "Marked " . count($downloadedIds) . " documents as downloaded\n";
    }
}
