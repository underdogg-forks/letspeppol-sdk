<?php

namespace LetsPeppolSdk\Resources;

/**
 * Proxy API Client for LetsPeppol document transmission
 *
 * Handles document exchange between Peppol Access Points.
 * All methods require JWT authentication.
 */
class ProxyClient extends BaseResource
{
    /**
     * Get all new documents received from Peppol network
     *
     * Retrieves undownloaded documents from the proxy.
     *
     * **Request:**
     * - GET /sapi/document?size=100
     * - Requires: JWT token
     *
     * **Response JSON:**
     * ```json
     * [
     *   {
     *     "id": "doc123",
     *     "documentType": "INVOICE",
     *     "direction": "INCOMING",
     *     "senderPeppolId": "0208:BE0987654321",
     *     "receiverPeppolId": "0208:BE0123456789",
     *     "ubl": "<Invoice>...</Invoice>",
     *     "receivedAt": "2024-01-09T10:30:00Z",
     *     "status": "RECEIVED"
     *   }
     * ]
     * ```
     *
     * **Example:**
     * ```php
     * $newDocs = $client->proxy()->getAllNewDocuments(50);
     * foreach ($newDocs as $doc) {
     *     echo "Received {$doc['documentType']} from {$doc['senderPeppolId']}\n";
     *     // Process document...
     *     $client->proxy()->markDownloaded($doc['id']);
     * }
     * ```
     *
     * @param int $size Maximum number of documents to retrieve (default: 100)
     * @return array Array of new documents with UBL content
     * @throws ApiException When not authenticated (401) or server error
     */
    public function getAllNewDocuments(int $size = 100): array
    {
        return $this->get('/sapi/document', ['size' => $size]);
    }

    /**
     * Get status updates for specific documents
     *
     * Check transmission status for multiple documents.
     *
     * **Request JSON:**
     * ```json
     * ["doc123", "doc456", "doc789"]
     * ```
     *
     * **Response JSON:**
     * ```json
     * [
     *   {
     *     "id": "doc123",
     *     "status": "DELIVERED",
     *     "statusMessage": "Document successfully delivered",
     *     "lastUpdated": "2024-01-09T10:35:00Z"
     *   },
     *   {
     *     "id": "doc456",
     *     "status": "PENDING",
     *     "statusMessage": "Awaiting delivery",
     *     "lastUpdated": "2024-01-09T10:30:00Z"
     *   }
     * ]
     * ```
     *
     * **Example:**
     * ```php
     * $statuses = $client->proxy()->getStatusUpdates(['doc123', 'doc456']);
     * foreach ($statuses as $status) {
     *     echo "{$status['id']}: {$status['status']} - {$status['statusMessage']}\n";
     * }
     * ```
     *
     * @param array $documentIds Array of document IDs to check
     * @return array Status information for each document
     * @throws ApiException When documents not found (404)
     */
    public function getStatusUpdates(array $documentIds): array
    {
        return $this->post('/sapi/document/status', $documentIds);
    }

    /**
     * Get document by ID
     *
     * Retrieve full document details including UBL content.
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": "doc123",
     *   "documentType": "INVOICE",
     *   "direction": "OUTGOING",
     *   "senderPeppolId": "0208:BE0123456789",
     *   "receiverPeppolId": "0208:BE0987654321",
     *   "ubl": "<Invoice>...</Invoice>",
     *   "status": "SENT",
     *   "createdAt": "2024-01-09T10:00:00Z",
     *   "sentAt": "2024-01-09T10:05:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $document = $client->proxy()->getDocument('doc123');
     * echo "Document type: " . $document['documentType'];
     * echo "Status: " . $document['status'];
     * file_put_contents('invoice.xml', $document['ubl']);
     * ```
     *
     * @param string $id Document ID
     * @return array Document details with UBL content
     * @throws ApiException When document not found (404)
     */
    public function getDocument(string $id): array
    {
        return $this->get("/sapi/document/" . rawurlencode($id));
    }

    /**
     * Create document to send via Peppol network
     *
     * Creates a new document for transmission.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "ownerPeppolId": "0208:BE0123456789",
     *   "counterPartyPeppolId": "0208:BE0987654321",
     *   "ubl": "<Invoice>...</Invoice>",
     *   "direction": "OUTGOING",
     *   "documentType": "INVOICE"
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": "doc123",
     *   "status": "PENDING",
     *   "createdAt": "2024-01-09T10:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $doc = $client->proxy()->createDocument([
     *     'ownerPeppolId' => '0208:BE0123456789',
     *     'counterPartyPeppolId' => '0208:BE0987654321',
     *     'ubl' => $ublXmlContent,
     *     'direction' => 'OUTGOING',
     *     'documentType' => 'INVOICE'
     * ]);
     * echo "Document created: " . $doc['id'];
     * ```
     *
     * @param array $documentData Document data (ownerPeppolId, counterPartyPeppolId, ubl, direction, documentType)
     * @param bool $noArchive If true, document won't be archived (default: false)
     * @return array Created document with ID and status
     * @throws ApiException When validation fails (422) or UBL invalid (400)
     */
    public function createDocument(array $documentData, bool $noArchive = false): array
    {
        return $this->request('POST', '/sapi/document', [
            'json'  => $documentData,
            'query' => ['noArchive' => $noArchive ? 'true' : 'false'],
        ]);
    }

    /**
     * Update document
     *
     * Modifies an existing document before it's sent.
     *
     * **Request JSON:** Same as createDocument
     *
     * **Example:**
     * ```php
     * $updated = $client->proxy()->updateDocument('doc123', [
     *     'ownerPeppolId' => '0208:BE0123456789',
     *     'counterPartyPeppolId' => '0208:BE0987654321',
     *     'ubl' => $updatedUblXml,
     *     'direction' => 'OUTGOING',
     *     'documentType' => 'INVOICE'
     * ]);
     * ```
     *
     * @param string $id Document ID
     * @param array $documentData Updated document data
     * @param bool $noArchive If true, document won't be archived (default: false)
     * @return array Updated document information
     * @throws ApiException When document not found (404) or already sent (409)
     */
    public function updateDocument(string $id, array $documentData, bool $noArchive = false): array
    {
        return $this->request('PUT', "/sapi/document/" . rawurlencode($id), [
            'json'  => $documentData,
            'query' => ['noArchive' => $noArchive ? 'true' : 'false'],
        ]);
    }

    /**
     * Reschedule document sending
     *
     * Changes the scheduled send time for a document.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "scheduledAt": "2024-01-10T09:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $result = $client->proxy()->rescheduleDocument('doc123', [
     *     'scheduledAt' => '2024-01-10T09:00:00Z'
     * ]);
     * ```
     *
     * @param string $id Document ID
     * @param array $documentData Scheduling data (scheduledAt)
     * @return array Updated document with new schedule
     * @throws ApiException When document not found (404) or already sent (409)
     */
    public function rescheduleDocument(string $id, array $documentData): array
    {
        return $this->put("/sapi/document/" . rawurlencode($id) . "/send", $documentData);
    }

    /**
     * Mark document as downloaded
     *
     * Marks a received document as downloaded so it won't appear in new documents list.
     *
     * **Example:**
     * ```php
     * $client->proxy()->markDownloaded('doc123');
     * ```
     *
     * @param string $id Document ID
     * @param bool $noArchive If true, document won't be archived (default: false)
     * @return void
     * @throws ApiException When document not found (404)
     */
    public function markDownloaded(string $id, bool $noArchive = false): void
    {
        $this->request('PUT', "/sapi/document/" . rawurlencode($id) . "/downloaded", [
            'query' => ['noArchive' => $noArchive ? 'true' : 'false'],
        ]);
    }

    /**
     * Mark multiple documents as downloaded
     *
     * Batch operation to mark multiple received documents as downloaded.
     *
     * **Example:**
     * ```php
     * $client->proxy()->markDownloadedBatch(['doc123', 'doc456', 'doc789']);
     * ```
     *
     * @param array $documentIds Array of document IDs to mark as downloaded
     * @param bool $noArchive If true, documents won't be archived (default: false)
     * @return void
     * @throws ApiException When request fails
     */
    public function markDownloadedBatch(array $documentIds, bool $noArchive = false): void
    {
        $this->request('PUT', '/sapi/document/downloaded', [
            'json'  => $documentIds,
            'query' => ['noArchive' => $noArchive ? 'true' : 'false'],
        ]);
    }

    /**
     * Cancel/delete document
     *
     * Deletes a document from the system.
     *
     * **Example:**
     * ```php
     * $client->proxy()->deleteDocument('doc123');
     * ```
     *
     * @param string $id Document ID
     * @param bool $noArchive If true, document won't be archived (default: false)
     * @return void
     * @throws ApiException When document not found (404) or cannot be deleted (409)
     */
    public function deleteDocument(string $id, bool $noArchive = false): void
    {
        $this->request('DELETE', "/sapi/document/" . rawurlencode($id), [
            'query' => ['noArchive' => $noArchive ? 'true' : 'false'],
        ]);
    }

    /**
     * Get registry information
     *
     * Retrieves current Peppol Access Point registry information for the authenticated company.
     *
     * **Request:**
     * - GET /sapi/registry
     * - Requires: JWT token
     *
     * **Response JSON:**
     * ```json
     * {
     *   "peppolId": "0208:BE0123456789",
     *   "registered": true,
     *   "capabilities": ["SEND", "RECEIVE"],
     *   "accessPointUrl": "https://ap.example.com",
     *   "registeredAt": "2024-01-01T10:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $registry = $client->proxy()->getRegistry();
     * if ($registry['registered']) {
     *     echo "Registered on Access Point\n";
     *     echo "Capabilities: " . implode(', ', $registry['capabilities']) . "\n";
     * }
     * ```
     *
     * @return array Registry information including registration status
     * @throws ApiException When not authenticated (401)
     */
    public function getRegistry(): array
    {
        return $this->get('/sapi/registry');
    }

    /**
     * Register on Access Point
     *
     * Registers company on Peppol Access Point to enable document transmission.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "peppolId": "0208:BE0123456789",
     *   "capabilities": ["SEND", "RECEIVE"]
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "peppolId": "0208:BE0123456789",
     *   "registered": true,
     *   "capabilities": ["SEND", "RECEIVE"],
     *   "accessPointUrl": "https://ap.example.com",
     *   "registeredAt": "2024-01-09T10:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $result = $client->proxy()->registerOnAccessPoint([
     *     'peppolId' => '0208:BE0123456789',
     *     'capabilities' => ['SEND', 'RECEIVE']
     * ]);
     * echo "Registered on: {$result['accessPointUrl']}\n";
     * ```
     *
     * @param array $registrationData Registration details (peppolId, capabilities)
     * @return array Registration result with access point details
     * @throws ApiException When registration fails (400) or already registered (409)
     */
    public function registerOnAccessPoint(array $registrationData): array
    {
        return $this->post('/sapi/registry', $registrationData);
    }

    /**
     * Unregister from Access Point
     *
     * Removes company registration from Peppol Access Point.
     *
     * **Request:**
     * - PUT /sapi/registry/unregister
     * - Requires: JWT token
     *
     * **Response JSON:**
     * ```json
     * {
     *   "peppolId": "0208:BE0123456789",
     *   "registered": false,
     *   "unregisteredAt": "2024-01-09T11:00:00Z"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $result = $client->proxy()->unregisterFromAccessPoint();
     * echo "Unregistered at: {$result['unregisteredAt']}\n";
     * ```
     *
     * @return array Unregistration result
     * @throws ApiException When not registered (404) or unregistration fails
     */
    public function unregisterFromAccessPoint(): array
    {
        return $this->request('PUT', '/sapi/registry/unregister');
    }

    /**
     * Remove from registry
     *
     * Completely removes registry entry from the system.
     *
     * **Request:**
     * - DELETE /sapi/registry
     * - Requires: JWT token
     *
     * **Example:**
     * ```php
     * $client->proxy()->deleteRegistry();
     * echo "Registry entry deleted\n";
     * ```
     *
     * @return void
     * @throws ApiException When not found (404) or deletion fails
     */
    public function deleteRegistry(): void
    {
        $this->delete('/sapi/registry');
    }

    /**
     * Health check
     *
     * Performs a health check on the proxy service to verify availability.
     *
     * **Request:**
     * - GET /api/monitor
     * - No authentication required (public endpoint)
     *
     * **Response:**
     * - Plain text status message (e.g., "OK", "Service is healthy")
     *
     * **Example:**
     * ```php
     * try {
     *     $status = $client->proxy()->healthCheck();
     *     echo "Service status: $status\n";
     * } catch (ApiException $e) {
     *     echo "Service unavailable: {$e->getMessage()}\n";
     * }
     * ```
     *
     * @return string Health status response
     * @throws ApiException When service is unavailable
     */
    public function healthCheck(): string
    {
        return $this->requestRaw('GET', '/api/monitor');
    }

    /**
     * Top up balance (for testing/monitoring)
     *
     * Adds balance to account for testing purposes. This is typically used in
     * development/staging environments for testing purposes.
     *
     * **Request:**
     * - GET /api/monitor/{amount}
     * - No authentication required (monitoring endpoint)
     *
     * **Response:**
     * - Plain text confirmation message
     *
     * **Example:**
     * ```php
     * $result = $client->proxy()->topUpBalance(100);
     * echo "Top-up result: $result\n";
     * ```
     *
     * @param int $amount Amount to add to balance
     * @return string Top-up result message
     * @throws ApiException When request fails
     */
    public function topUpBalance(int $amount): string
    {
        return $this->requestRaw('GET', "/api/monitor/" . rawurlencode((string)$amount));
    }
}
