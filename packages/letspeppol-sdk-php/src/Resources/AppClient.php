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
            'body' => $ublXml,
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
            'body' => $ublXml,
            'headers' => ['Content-Type' => 'text/xml'],
            'query' => $params,
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
            'body' => $ublXml,
            'headers' => ['Content-Type' => 'text/xml'],
            'query' => $params,
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
            'query' => $params
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
     */
    public function getCompany(): array
    {
        return $this->get('/sapi/company');
    }

    /**
     * Update company information
     */
    public function updateCompany(array $companyData): array
    {
        return $this->put('/sapi/company', $companyData);
    }

    // ==================== Partners ====================

    /**
     * List partners
     */
    public function listPartners(): array
    {
        return $this->get('/sapi/partner');
    }

    /**
     * Search partners by Peppol ID
     */
    public function searchPartners(string $peppolId): array
    {
        return $this->get('/sapi/partner/search', ['peppolId' => $peppolId]);
    }

    /**
     * Create partner
     */
    public function createPartner(array $partnerData): array
    {
        return $this->post('/sapi/partner', $partnerData);
    }

    /**
     * Update partner
     */
    public function updatePartner(int $id, array $partnerData): array
    {
        return $this->put("/sapi/partner/" . rawurlencode((string)$id), $partnerData);
    }

    /**
     * Delete partner
     */
    public function deletePartner(int $id): void
    {
        $this->delete("/sapi/partner/" . rawurlencode((string)$id));
    }

    // ==================== Products ====================

    /**
     * List products
     */
    public function listProducts(): array
    {
        return $this->get('/sapi/product');
    }

    /**
     * Create product
     */
    public function createProduct(array $productData): array
    {
        return $this->post('/sapi/product', $productData);
    }

    /**
     * Update product
     */
    public function updateProduct(int $id, array $productData): array
    {
        return $this->put("/sapi/product/" . rawurlencode((string)$id), $productData);
    }

    /**
     * Delete product
     */
    public function deleteProduct(int $id): void
    {
        $this->delete("/sapi/product/" . rawurlencode((string)$id));
    }

    // ==================== Product Categories ====================

    /**
     * List root categories
     */
    public function listRootCategories(bool $deep = false): array
    {
        return $this->get('/sapi/product-category', ['deep' => $deep ? 'true' : 'false']);
    }

    /**
     * List all categories flat
     */
    public function listAllCategoriesFlat(): array
    {
        return $this->get('/sapi/product-category/all');
    }

    /**
     * Get category by ID
     */
    public function getCategory(int $id, bool $deep = false): array
    {
        return $this->get("/sapi/product-category/" . rawurlencode((string)$id), ['deep' => $deep ? 'true' : 'false']);
    }

    /**
     * Create category
     */
    public function createCategory(array $categoryData): array
    {
        return $this->post('/sapi/product-category', $categoryData);
    }

    /**
     * Update category
     */
    public function updateCategory(int $id, array $categoryData): array
    {
        return $this->put("/sapi/product-category/" . rawurlencode((string)$id), $categoryData);
    }

    /**
     * Delete category
     */
    public function deleteCategory(int $id): void
    {
        $this->delete("/sapi/product-category/" . rawurlencode((string)$id));
    }

    // ==================== Statistics ====================

    /**
     * Get donation statistics
     */
    public function getDonationStats(): array
    {
        return $this->get('/api/stats/donation');
    }

    /**
     * Get account totals
     */
    public function getAccountTotals(): array
    {
        return $this->get('/sapi/stats/account');
    }

    // ==================== Peppol Directory ====================

    /**
     * Search Peppol Directory
     */
    public function searchPeppolDirectory(?string $name = null, ?string $participant = null): array
    {
        $params = array_filter([
            'name' => $name,
            'participant' => $participant,
        ], function ($value) {
            return $value !== null;
        });

        return $this->get('/api/peppol-directory', $params);
    }
}
