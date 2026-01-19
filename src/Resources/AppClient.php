<?php

namespace LetsPeppolSdk\Resources;

/**
 * App API Client for LetsPeppol application management
 *
 * Provides document management, partner management, and business logic.
 * All methods require JWT authentication.
 */
class AppClient extends BaseResource
{
    // ==================== Documents ====================

    /**
     * Validate UBL XML document
     *
     * Validates UBL XML against Peppol BIS 3.0 rules before sending.
     *
     * **Request:**
     * - POST /sapi/document/validate
     * - Content-Type: text/xml
     * - Body: UBL XML string
     *
     * **Response JSON:**
     * ```json
     * {
     *   "valid": true,
     *   "errors": [],
     *   "warnings": [
     *     "Optional field 'PaymentTerms' is missing"
     *   ]
     * }
     * ```
     *
     * Or when invalid:
     * ```json
     * {
     *   "valid": false,
     *   "errors": [
     *     "Invoice number is required",
     *     "Invalid VAT number format"
     *   ],
     *   "warnings": []
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $validation = $client->app()->validateDocument($ublXml);
     * if ($validation['valid']) {
     *     echo "Document is valid!";
     *     $doc = $client->app()->createDocument($ublXml);
     * } else {
     *     echo "Validation errors:\n";
     *     foreach ($validation['errors'] as $error) {
     *         echo "- $error\n";
     *     }
     * }
     * ```
     *
     * @param string $ublXml UBL XML content to validate
     * @return array Validation result with valid flag, errors, and warnings
     * @throws ApiException When XML is malformed (400)
     */
    public function validateDocument(string $ublXml): array
    {
        return $this->request('POST', '/sapi/document/validate', [
            'body'    => $ublXml,
            'headers' => ['Content-Type' => 'text/xml'],
        ]);
    }

    /**
     * List documents with filtering and pagination
     *
     * Retrieves documents with optional filters.
     *
     * **Request:**
     * - GET /sapi/document?type=INVOICE&direction=INCOMING&page=0&size=20
     *
     * **Filters:**
     * - type: INVOICE, CREDIT_NOTE
     * - direction: INCOMING, OUTGOING
     * - draft: true, false
     * - read: true, false
     * - paid: true, false
     *
     * **Response JSON:**
     * ```json
     * {
     *   "content": [
     *     {
     *       "id": "doc123",
     *       "documentType": "INVOICE",
     *       "direction": "INCOMING",
     *       "invoiceNumber": "INV-2024-001",
     *       "issueDate": "2024-01-09",
     *       "dueDate": "2024-02-09",
     *       "total": 1000.00,
     *       "currency": "EUR",
     *       "partnerName": "Supplier Company",
     *       "partnerPeppolId": "0208:BE0987654321",
     *       "read": false,
     *       "paid": false
     *     }
     *   ],
     *   "page": 0,
     *   "size": 20,
     *   "totalElements": 150,
     *   "totalPages": 8
     * }
     * ```
     *
     * **Example:**
     * ```php
     * // Get unread incoming invoices
     * $result = $client->app()->listDocuments([
     *     'type' => 'INVOICE',
     *     'direction' => 'INCOMING',
     *     'read' => false
     * ], 0, 20);
     *
     * echo "Found {$result['totalElements']} invoices\n";
     * foreach ($result['content'] as $doc) {
     *     echo "- {$doc['invoiceNumber']}: {$doc['total']} {$doc['currency']}\n";
     * }
     * ```
     *
     * @param array $filters Filter criteria (type, direction, draft, read, paid)
     * @param int $page Page number (default: 0)
     * @param int $size Page size (default: 20)
     * @param string|null $sort Sort field with direction (e.g., "issueDate,desc")
     * @return array Paginated document list with metadata
     * @throws ApiException When invalid filter values (400)
     */
    public function listDocuments(array $filters = [], int $page = 0, int $size = 20, ?string $sort = null): array
    {
        $params = array_merge($filters, [
            'page' => $page,
            'size' => $size,
        ]);

        if ($sort) {
            $params['sort'] = $sort;
        }

        return $this->get('/sapi/document', $params);
    }

    /**
     * Get document by ID
     *
     * Retrieves full document details including UBL XML.
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": "doc123",
     *   "documentType": "INVOICE",
     *   "direction": "INCOMING",
     *   "invoiceNumber": "INV-2024-001",
     *   "issueDate": "2024-01-09",
     *   "dueDate": "2024-02-09",
     *   "total": 1000.00,
     *   "currency": "EUR",
     *   "partnerName": "Supplier Company",
     *   "partnerPeppolId": "0208:BE0987654321",
     *   "ubl": "<Invoice>...</Invoice>",
     *   "read": false,
     *   "paid": false,
     *   "lineItems": [...]
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $doc = $client->app()->getDocument('doc123');
     * echo "Invoice: {$doc['invoiceNumber']}\n";
     * echo "From: {$doc['partnerName']}\n";
     * echo "Total: {$doc['total']} {$doc['currency']}\n";
     * file_put_contents('invoice.xml', $doc['ubl']);
     * ```
     *
     * @param string $id Document ID
     * @return array Complete document details with UBL content
     * @throws ApiException When document not found (404)
     */
    public function getDocument(string $id): array
    {
        return $this->get("/sapi/document/" . rawurlencode($id));
    }

    /**
     * Create document from UBL XML
     *
     * Creates a new document in the system.
     *
     * **Request:**
     * - POST /sapi/document?draft=false&schedule=2024-01-10T09:00:00Z
     * - Content-Type: text/xml
     * - Body: UBL XML string
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": "doc123",
     *   "documentType": "INVOICE",
     *   "invoiceNumber": "INV-2024-001",
     *   "status": "DRAFT",
     *   "createdAt": "2024-01-09T10:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * // Create as draft
     * $doc = $client->app()->createDocument($ublXml, true);
     * echo "Draft created: {$doc['id']}\n";
     *
     * // Create and schedule send
     * $doc = $client->app()->createDocument($ublXml, false, '2024-01-10T09:00:00Z');
     * echo "Scheduled for: {$doc['scheduledAt']}\n";
     * ```
     *
     * @param string $ublXml UBL XML content
     * @param bool $draft If true, save as draft without sending (default: false)
     * @param string|null $schedule ISO 8601 datetime to schedule sending
     * @return array Created document with ID and status
     * @throws ApiException When validation fails (422) or UBL invalid (400)
     */
    public function createDocument(string $ublXml, bool $draft = false, ?string $schedule = null): array
    {
        $params = ['draft' => $draft ? 'true' : 'false'];
        if ($schedule) {
            $params['schedule'] = $schedule;
        }

        return $this->request('POST', '/sapi/document', [
            'body'    => $ublXml,
            'headers' => ['Content-Type' => 'text/xml'],
            'query'   => $params,
        ]);
    }

    /**
     * Update document
     *
     * Updates an existing draft document with new UBL content.
     *
     * **Example:**
     * ```php
     * $updated = $client->app()->updateDocument('doc123', $newUblXml, true);
     * echo "Document updated: {$updated['id']}\n";
     * ```
     *
     * @param string $id Document ID
     * @param string $ublXml Updated UBL XML content
     * @param bool $draft Keep as draft (default: false)
     * @param string|null $schedule New scheduled send time
     * @return array Updated document details
     * @throws ApiException When document not found (404) or already sent (409)
     */
    public function updateDocument(string $id, string $ublXml, bool $draft = false, ?string $schedule = null): array
    {
        $params = ['draft' => $draft ? 'true' : 'false'];
        if ($schedule) {
            $params['schedule'] = $schedule;
        }

        return $this->request('PUT', "/sapi/document/" . rawurlencode($id), [
            'body'    => $ublXml,
            'headers' => ['Content-Type' => 'text/xml'],
            'query'   => $params,
        ]);
    }

    /**
     * Send document
     *
     * Triggers sending of a document via Peppol network.
     *
     * **Example:**
     * ```php
     * // Send immediately
     * $result = $client->app()->sendDocument('doc123');
     *
     * // Schedule send
     * $result = $client->app()->sendDocument('doc123', '2024-01-10T09:00:00Z');
     * ```
     *
     * @param string $id Document ID
     * @param string|null $schedule Optional ISO 8601 datetime to schedule sending
     * @return array Document status after send trigger
     * @throws ApiException When document not found (404), not ready (422), or already sent (409)
     */
    public function sendDocument(string $id, ?string $schedule = null): array
    {
        $params = [];
        if ($schedule) {
            $params['schedule'] = $schedule;
        }

        return $this->request('PUT', "/sapi/document/" . rawurlencode($id) . "/send", [
            'query' => $params,
        ]);
    }

    /**
     * Mark document as read
     *
     * Updates document status to indicate it has been read by the user.
     *
     * **Example:**
     * ```php
     * $result = $client->app()->markDocumentRead('doc123');
     * ```
     *
     * @param string $id Document ID
     * @return array Updated document status
     * @throws ApiException When document not found (404)
     */
    public function markDocumentRead(string $id): array
    {
        return $this->request('PUT', "/sapi/document/" . rawurlencode($id) . "/read");
    }

    /**
     * Mark document as paid
     *
     * Updates document status to indicate payment has been received/made.
     *
     * **Example:**
     * ```php
     * $result = $client->app()->markDocumentPaid('doc123');
     * ```
     *
     * @param string $id Document ID
     * @return array Updated document status
     * @throws ApiException When document not found (404)
     */
    public function markDocumentPaid(string $id): array
    {
        return $this->request('PUT', "/sapi/document/" . rawurlencode($id) . "/paid");
    }

    /**
     * Delete document
     *
     * Permanently deletes a document from the system.
     *
     * **Example:**
     * ```php
     * $client->app()->deleteDocument('doc123');
     * ```
     *
     * @param string $id Document ID
     * @return void
     * @throws ApiException When document not found (404) or cannot be deleted (409)
     */
    public function deleteDocument(string $id): void
    {
        $this->delete("/sapi/document/" . rawurlencode($id));
    }

    // ==================== Company ====================

    /**
     * Get company information
     *
     * Retrieves full company profile for the authenticated user.
     *
     * **Request:**
     * - GET /sapi/company
     * - Requires: JWT token
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": 123,
     *   "peppolId": "0208:BE0123456789",
     *   "vatNumber": "BE0123456789",
     *   "name": "Company Name BVBA",
     *   "email": "admin@company.com",
     *   "address": {
     *     "street": "Street Name 123",
     *     "city": "Brussels",
     *     "postalCode": "1000",
     *     "country": "BE"
     *   },
     *   "phone": "+32 2 123 4567",
     *   "website": "https://company.com"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $company = $client->app()->getCompany();
     * echo "Company: {$company['name']}\n";
     * echo "Peppol ID: {$company['peppolId']}\n";
     * ```
     *
     * @return array Company information
     * @throws ApiException When not authenticated (401)
     */
    public function getCompany(): array
    {
        return $this->get('/sapi/company');
    }

    /**
     * Update company information
     *
     * Updates company profile details for the authenticated user.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "name": "Updated Company Name BVBA",
     *   "email": "newemail@company.com",
     *   "address": {
     *     "street": "New Street 456",
     *     "city": "Antwerp",
     *     "postalCode": "2000",
     *     "country": "BE"
     *   },
     *   "phone": "+32 3 987 6543",
     *   "website": "https://newcompany.com"
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": 123,
     *   "peppolId": "0208:BE0123456789",
     *   "vatNumber": "BE0123456789",
     *   "name": "Updated Company Name BVBA",
     *   "email": "newemail@company.com",
     *   "address": {
     *     "street": "New Street 456",
     *     "city": "Antwerp",
     *     "postalCode": "2000",
     *     "country": "BE"
     *   },
     *   "phone": "+32 3 987 6543",
     *   "website": "https://newcompany.com"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $updated = $client->app()->updateCompany([
     *     'name' => 'Updated Company Name BVBA',
     *     'email' => 'newemail@company.com'
     * ]);
     * echo "Company updated: {$updated['name']}\n";
     * ```
     *
     * @param array $companyData Company data to update
     * @return array Updated company information
     * @throws ApiException When not authenticated (401) or validation fails (422)
     */
    public function updateCompany(array $companyData): array
    {
        return $this->put('/sapi/company', $companyData);
    }

    // ==================== Partners ====================

    /**
     * List partners
     *
     * Retrieves all business partners in the system.
     *
     * **Request:**
     * - GET /sapi/partner
     * - Requires: JWT token
     *
     * **Response JSON:**
     * ```json
     * [
     *   {
     *     "id": 1,
     *     "name": "Partner Company Ltd",
     *     "peppolId": "0208:BE0987654321",
     *     "vatNumber": "BE0987654321",
     *     "email": "contact@partner.com",
     *     "country": "BE"
     *   },
     *   {
     *     "id": 2,
     *     "name": "Another Partner BV",
     *     "peppolId": "0208:NL123456789B01",
     *     "vatNumber": "NL123456789B01",
     *     "email": "info@another.nl",
     *     "country": "NL"
     *   }
     * ]
     * ```
     *
     * **Example:**
     * ```php
     * $partners = $client->app()->listPartners();
     * foreach ($partners as $partner) {
     *     echo "{$partner['name']} ({$partner['peppolId']})\n";
     * }
     * ```
     *
     * @return array Array of partner objects
     * @throws ApiException When not authenticated (401)
     */
    public function listPartners(): array
    {
        return $this->get('/sapi/partner');
    }

    /**
     * Search partners by Peppol ID
     *
     * Searches for partners matching the given Peppol ID in the directory.
     *
     * **Request:**
     * - GET /sapi/partner/search?peppolId=0208:BE0987654321
     *
     * **Response JSON:**
     * ```json
     * [
     *   {
     *     "peppolId": "0208:BE0987654321",
     *     "name": "Partner Company Ltd",
     *     "country": "BE"
     *   }
     * ]
     * ```
     *
     * **Example:**
     * ```php
     * $results = $client->app()->searchPartners('0208:BE0987654321');
     * if (!empty($results)) {
     *     echo "Found: {$results[0]['name']}\n";
     * }
     * ```
     *
     * @param string $peppolId Peppol participant ID to search for
     * @return array Array of matching partners
     * @throws ApiException When search fails (400)
     */
    public function searchPartners(string $peppolId): array
    {
        return $this->get('/sapi/partner/search', ['peppolId' => $peppolId]);
    }

    /**
     * Create partner
     *
     * Adds a new business partner to the system.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "name": "New Partner Ltd",
     *   "peppolId": "0208:BE0111222333",
     *   "vatNumber": "BE0111222333",
     *   "email": "contact@newpartner.com",
     *   "country": "BE",
     *   "address": {
     *     "street": "Partner Street 789",
     *     "city": "Ghent",
     *     "postalCode": "9000",
     *     "country": "BE"
     *   }
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": 3,
     *   "name": "New Partner Ltd",
     *   "peppolId": "0208:BE0111222333",
     *   "vatNumber": "BE0111222333",
     *   "email": "contact@newpartner.com",
     *   "country": "BE",
     *   "createdAt": "2024-01-09T10:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $partner = $client->app()->createPartner([
     *     'name' => 'New Partner Ltd',
     *     'peppolId' => '0208:BE0111222333',
     *     'email' => 'contact@newpartner.com'
     * ]);
     * echo "Partner created with ID: {$partner['id']}\n";
     * ```
     *
     * @param array $partnerData Partner information
     * @return array Created partner with ID
     * @throws ApiException When validation fails (422) or partner already exists (409)
     */
    public function createPartner(array $partnerData): array
    {
        return $this->post('/sapi/partner', $partnerData);
    }

    /**
     * Update partner
     *
     * Updates an existing partner's information.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "name": "Updated Partner Name",
     *   "email": "newemail@partner.com",
     *   "address": {
     *     "street": "New Address 123",
     *     "city": "Brussels",
     *     "postalCode": "1000",
     *     "country": "BE"
     *   }
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": 3,
     *   "name": "Updated Partner Name",
     *   "peppolId": "0208:BE0111222333",
     *   "vatNumber": "BE0111222333",
     *   "email": "newemail@partner.com",
     *   "updatedAt": "2024-01-09T11:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $updated = $client->app()->updatePartner(3, [
     *     'name' => 'Updated Partner Name',
     *     'email' => 'newemail@partner.com'
     * ]);
     * echo "Partner updated: {$updated['name']}\n";
     * ```
     *
     * @param int $id Partner ID
     * @param array $partnerData Updated partner data
     * @return array Updated partner information
     * @throws ApiException When partner not found (404) or validation fails (422)
     */
    public function updatePartner(int $id, array $partnerData): array
    {
        return $this->put("/sapi/partner/" . rawurlencode((string)$id), $partnerData);
    }

    /**
     * Delete partner
     *
     * Removes a partner from the system.
     *
     * **Example:**
     * ```php
     * $client->app()->deletePartner(3);
     * echo "Partner deleted successfully\n";
     * ```
     *
     * @param int $id Partner ID to delete
     * @return void
     * @throws ApiException When partner not found (404) or has active documents (409)
     */
    public function deletePartner(int $id): void
    {
        $this->delete("/sapi/partner/" . rawurlencode((string)$id));
    }

    // ==================== Products ====================

    /**
     * List products
     *
     * Retrieves all products from the catalog.
     *
     * **Request:**
     * - GET /sapi/product
     * - Requires: JWT token
     *
     * **Response JSON:**
     * ```json
     * [
     *   {
     *     "id": 1,
     *     "name": "Product A",
     *     "sku": "PROD-001",
     *     "description": "High quality product",
     *     "price": 99.99,
     *     "currency": "EUR",
     *     "vatRate": 21.0,
     *     "categoryId": 5,
     *     "inStock": true
     *   },
     *   {
     *     "id": 2,
     *     "name": "Product B",
     *     "sku": "PROD-002",
     *     "description": "Premium service",
     *     "price": 149.99,
     *     "currency": "EUR",
     *     "vatRate": 21.0,
     *     "inStock": true
     *   }
     * ]
     * ```
     *
     * **Example:**
     * ```php
     * $products = $client->app()->listProducts();
     * foreach ($products as $product) {
     *     echo "{$product['name']}: {$product['price']} {$product['currency']}\n";
     * }
     * ```
     *
     * @return array Array of product objects
     * @throws ApiException When not authenticated (401)
     */
    public function listProducts(): array
    {
        return $this->get('/sapi/product');
    }

    /**
     * Create product
     *
     * Adds a new product to the catalog.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "name": "New Product",
     *   "sku": "PROD-003",
     *   "description": "Excellent new product",
     *   "price": 79.99,
     *   "currency": "EUR",
     *   "vatRate": 21.0,
     *   "categoryId": 5,
     *   "inStock": true
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": 3,
     *   "name": "New Product",
     *   "sku": "PROD-003",
     *   "description": "Excellent new product",
     *   "price": 79.99,
     *   "currency": "EUR",
     *   "vatRate": 21.0,
     *   "categoryId": 5,
     *   "inStock": true,
     *   "createdAt": "2024-01-09T10:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $product = $client->app()->createProduct([
     *     'name' => 'New Product',
     *     'sku' => 'PROD-003',
     *     'price' => 79.99,
     *     'currency' => 'EUR'
     * ]);
     * echo "Product created with ID: {$product['id']}\n";
     * ```
     *
     * @param array $productData Product information
     * @return array Created product with ID
     * @throws ApiException When validation fails (422) or SKU already exists (409)
     */
    public function createProduct(array $productData): array
    {
        return $this->post('/sapi/product', $productData);
    }

    /**
     * Update product
     *
     * Updates an existing product's information.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "name": "Updated Product Name",
     *   "price": 89.99,
     *   "description": "Updated description",
     *   "inStock": false
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": 3,
     *   "name": "Updated Product Name",
     *   "sku": "PROD-003",
     *   "description": "Updated description",
     *   "price": 89.99,
     *   "currency": "EUR",
     *   "inStock": false,
     *   "updatedAt": "2024-01-09T11:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $updated = $client->app()->updateProduct(3, [
     *     'name' => 'Updated Product Name',
     *     'price' => 89.99
     * ]);
     * echo "Product updated: {$updated['name']}\n";
     * ```
     *
     * @param int $id Product ID
     * @param array $productData Updated product data
     * @return array Updated product information
     * @throws ApiException When product not found (404) or validation fails (422)
     */
    public function updateProduct(int $id, array $productData): array
    {
        return $this->put("/sapi/product/" . rawurlencode((string)$id), $productData);
    }

    /**
     * Delete product
     *
     * Removes a product from the catalog.
     *
     * **Example:**
     * ```php
     * $client->app()->deleteProduct(3);
     * echo "Product deleted successfully\n";
     * ```
     *
     * @param int $id Product ID to delete
     * @return void
     * @throws ApiException When product not found (404) or in use in documents (409)
     */
    public function deleteProduct(int $id): void
    {
        $this->delete("/sapi/product/" . rawurlencode((string)$id));
    }

    // ==================== Product Categories ====================

    /**
     * List root categories
     *
     * Retrieves top-level product categories.
     *
     * **Request:**
     * - GET /sapi/product-category?deep=false
     *
     * **Response JSON (deep=false):**
     * ```json
     * [
     *   {
     *     "id": 1,
     *     "name": "Electronics",
     *     "parentId": null,
     *     "hasChildren": true
     *   },
     *   {
     *     "id": 2,
     *     "name": "Services",
     *     "parentId": null,
     *     "hasChildren": false
     *   }
     * ]
     * ```
     *
     * **Response JSON (deep=true):**
     * ```json
     * [
     *   {
     *     "id": 1,
     *     "name": "Electronics",
     *     "parentId": null,
     *     "children": [
     *       {
     *         "id": 5,
     *         "name": "Computers",
     *         "parentId": 1,
     *         "children": []
     *       }
     *     ]
     *   }
     * ]
     * ```
     *
     * **Example:**
     * ```php
     * // Get root categories only
     * $categories = $client->app()->listRootCategories();
     *
     * // Get full category tree
     * $tree = $client->app()->listRootCategories(true);
     * ```
     *
     * @param bool $deep If true, includes nested subcategories (default: false)
     * @return array Array of root category objects
     * @throws ApiException When not authenticated (401)
     */
    public function listRootCategories(bool $deep = false): array
    {
        return $this->get('/sapi/product-category', ['deep' => $deep ? 'true' : 'false']);
    }

    /**
     * List all categories flat
     *
     * Retrieves all categories in a flat array.
     *
     * **Response JSON:**
     * ```json
     * [
     *   {
     *     "id": 1,
     *     "name": "Electronics",
     *     "parentId": null
     *   },
     *   {
     *     "id": 5,
     *     "name": "Computers",
     *     "parentId": 1
     *   },
     *   {
     *     "id": 2,
     *     "name": "Services",
     *     "parentId": null
     *   }
     * ]
     * ```
     *
     * **Example:**
     * ```php
     * $allCategories = $client->app()->listAllCategoriesFlat();
     * foreach ($allCategories as $cat) {
     *     echo "{$cat['name']} (ID: {$cat['id']})\n";
     * }
     * ```
     *
     * @return array Array of all categories in flat structure
     * @throws ApiException When not authenticated (401)
     */
    public function listAllCategoriesFlat(): array
    {
        return $this->get('/sapi/product-category/all');
    }

    /**
     * Get category by ID
     *
     * Retrieves a specific category with optional subcategories.
     *
     * **Response JSON (deep=false):**
     * ```json
     * {
     *   "id": 1,
     *   "name": "Electronics",
     *   "parentId": null,
     *   "hasChildren": true
     * }
     * ```
     *
     * **Response JSON (deep=true):**
     * ```json
     * {
     *   "id": 1,
     *   "name": "Electronics",
     *   "parentId": null,
     *   "children": [
     *     {
     *       "id": 5,
     *       "name": "Computers",
     *       "parentId": 1,
     *       "children": []
     *     }
     *   ]
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $category = $client->app()->getCategory(1, true);
     * echo "Category: {$category['name']}\n";
     * echo "Subcategories: " . count($category['children']) . "\n";
     * ```
     *
     * @param int $id Category ID
     * @param bool $deep If true, includes nested subcategories (default: false)
     * @return array Category object with optional children
     * @throws ApiException When category not found (404)
     */
    public function getCategory(int $id, bool $deep = false): array
    {
        return $this->get("/sapi/product-category/" . rawurlencode((string)$id), ['deep' => $deep ? 'true' : 'false']);
    }

    /**
     * Create category
     *
     * Adds a new product category.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "name": "Software",
     *   "parentId": 1
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": 10,
     *   "name": "Software",
     *   "parentId": 1,
     *   "createdAt": "2024-01-09T10:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $category = $client->app()->createCategory([
     *     'name' => 'Software',
     *     'parentId' => 1
     * ]);
     * echo "Category created with ID: {$category['id']}\n";
     * ```
     *
     * @param array $categoryData Category information (name, optional parentId)
     * @return array Created category with ID
     * @throws ApiException When validation fails (422) or parent not found (404)
     */
    public function createCategory(array $categoryData): array
    {
        return $this->post('/sapi/product-category', $categoryData);
    }

    /**
     * Update category
     *
     * Updates an existing category's information.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "name": "Updated Category Name",
     *   "parentId": 2
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": 10,
     *   "name": "Updated Category Name",
     *   "parentId": 2,
     *   "updatedAt": "2024-01-09T11:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $updated = $client->app()->updateCategory(10, [
     *     'name' => 'Updated Category Name'
     * ]);
     * echo "Category updated: {$updated['name']}\n";
     * ```
     *
     * @param int $id Category ID
     * @param array $categoryData Updated category data
     * @return array Updated category information
     * @throws ApiException When category not found (404) or validation fails (422)
     */
    public function updateCategory(int $id, array $categoryData): array
    {
        return $this->put("/sapi/product-category/" . rawurlencode((string)$id), $categoryData);
    }

    /**
     * Delete category
     *
     * Removes a product category.
     *
     * **Example:**
     * ```php
     * $client->app()->deleteCategory(10);
     * echo "Category deleted successfully\n";
     * ```
     *
     * @param int $id Category ID to delete
     * @return void
     * @throws ApiException When category not found (404) or has products/children (409)
     */
    public function deleteCategory(int $id): void
    {
        $this->delete("/sapi/product-category/" . rawurlencode((string)$id));
    }

    // ==================== Statistics ====================

    /**
     * Get donation statistics
     *
     * Retrieves platform-wide donation statistics.
     *
     * **Request:**
     * - GET /api/stats/donation
     * - No authentication required (public endpoint)
     *
     * **Response JSON:**
     * ```json
     * {
     *   "totalDonations": 25000.00,
     *   "currency": "EUR",
     *   "donorCount": 350,
     *   "averageDonation": 71.43,
     *   "lastUpdated": "2024-01-09T10:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $stats = $client->app()->getDonationStats();
     * echo "Total donations: {$stats['totalDonations']} {$stats['currency']}\n";
     * echo "Number of donors: {$stats['donorCount']}\n";
     * ```
     *
     * @return array Donation statistics
     * @throws ApiException When request fails
     */
    public function getDonationStats(): array
    {
        return $this->get('/api/stats/donation');
    }

    /**
     * Get account totals
     *
     * Retrieves financial totals for the authenticated account.
     *
     * **Request:**
     * - GET /sapi/stats/account
     * - Requires: JWT token
     *
     * **Response JSON:**
     * ```json
     * {
     *   "totalIncoming": 50000.00,
     *   "totalOutgoing": 35000.00,
     *   "unpaidIncoming": 5000.00,
     *   "unpaidOutgoing": 2000.00,
     *   "currency": "EUR",
     *   "documentCounts": {
     *     "incomingInvoices": 45,
     *     "outgoingInvoices": 32,
     *     "incomingCreditNotes": 3,
     *     "outgoingCreditNotes": 1
     *   },
     *   "period": {
     *     "from": "2024-01-01T00:00:00Z",
     *     "to": "2024-01-09T23:59:59Z"
     *   }
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $totals = $client->app()->getAccountTotals();
     * echo "Total incoming: {$totals['totalIncoming']} {$totals['currency']}\n";
     * echo "Total outgoing: {$totals['totalOutgoing']} {$totals['currency']}\n";
     * echo "Unpaid incoming: {$totals['unpaidIncoming']}\n";
     * ```
     *
     * @return array Account financial totals and document counts
     * @throws ApiException When not authenticated (401)
     */
    public function getAccountTotals(): array
    {
        return $this->get('/sapi/stats/account');
    }

    // ==================== Peppol Directory ====================

    /**
     * Search Peppol Directory
     *
     * Searches the public Peppol Directory for registered participants.
     *
     * **Request:**
     * - GET /api/peppol-directory?name=Company&participant=0208:BE0123456789
     * - At least one parameter required
     * - No authentication required (public endpoint)
     *
     * **Response JSON:**
     * ```json
     * [
     *   {
     *     "peppolId": "0208:BE0123456789",
     *     "name": "Company Name BVBA",
     *     "country": "BE",
     *     "geoInfo": {
     *       "latitude": 50.8503,
     *       "longitude": 4.3517
     *     },
     *     "registeredAt": "2023-06-15T10:00:00Z"
     *   },
     *   {
     *     "peppolId": "0208:BE0987654321",
     *     "name": "Another Company Ltd",
     *     "country": "BE",
     *     "geoInfo": {
     *       "latitude": 51.2194,
     *       "longitude": 4.4025
     *     },
     *     "registeredAt": "2023-08-20T14:30:00Z"
     *   }
     * ]
     * ```
     *
     * **Example:**
     * ```php
     * // Search by name
     * $results = $client->app()->searchPeppolDirectory(name: 'ACME Corporation');
     *
     * // Search by Peppol ID
     * $results = $client->app()->searchPeppolDirectory(participant: '0208:BE0123456789');
     *
     * // Combined search
     * $results = $client->app()->searchPeppolDirectory('Company', '0208:BE');
     *
     * foreach ($results as $company) {
     *     echo "{$company['name']} - {$company['peppolId']}\n";
     * }
     * ```
     *
     * @param string|null $name Company name to search for (partial match)
     * @param string|null $participant Peppol participant ID to search for (partial match)
     * @return array Array of matching companies from Peppol Directory
     * @throws ApiException When no search criteria provided (400)
     */
    public function searchPeppolDirectory(?string $name = null, ?string $participant = null): array
    {
        $params = array_filter([
            'name'        => $name,
            'participant' => $participant,
        ], function ($value) {
            return $value !== null;
        });

        return $this->get('/api/peppol-directory', $params);
    }
}
