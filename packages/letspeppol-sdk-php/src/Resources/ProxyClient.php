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
        return $this->get("/sapi/document/{$id}");
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
            'json' => $documentData,
            'query' => ['noArchive' => $noArchive ? 'true' : 'false']
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
        return $this->request('PUT', "/sapi/document/{$id}", [
            'json' => $documentData,
            'query' => ['noArchive' => $noArchive ? 'true' : 'false']
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
        return $this->put("/sapi/document/{$id}/send", $documentData);
    }

    /**
     * Mark document as downloaded
     */
    public function markDownloaded(string $id, bool $noArchive = false): void
    {
        $this->request('PUT', "/sapi/document/{$id}/downloaded", [
            'query' => ['noArchive' => $noArchive ? 'true' : 'false']
        ]);
    }

    /**
     * Mark multiple documents as downloaded
     */
    public function markDownloadedBatch(array $documentIds, bool $noArchive = false): void
    {
        $this->request('PUT', '/sapi/document/downloaded', [
            'json' => $documentIds,
            'query' => ['noArchive' => $noArchive ? 'true' : 'false']
        ]);
    }

    /**
     * Cancel/delete document
     */
    public function deleteDocument(string $id, bool $noArchive = false): void
    {
        $this->request('DELETE', "/sapi/document/{$id}", [
            'query' => ['noArchive' => $noArchive ? 'true' : 'false']
        ]);
    }

    /**
     * Get registry information
     */
    public function getRegistry(): array
    {
        return $this->get('/sapi/registry');
    }

    /**
     * Register on Access Point
     */
    public function registerOnAccessPoint(array $registrationData): array
    {
        return $this->post('/sapi/registry', $registrationData);
    }

    /**
     * Unregister from Access Point
     */
    public function unregisterFromAccessPoint(): array
    {
        return $this->request('PUT', '/sapi/registry/unregister');
    }

    /**
     * Remove from registry
     */
    public function deleteRegistry(): void
    {
        $this->delete('/sapi/registry');
    }

    /**
     * Health check
     */
    public function healthCheck(): string
    {
        return $this->requestRaw('GET', '/api/monitor');
    }

    /**
     * Top up balance (for testing/monitoring)
     */
    public function topUpBalance(int $amount): string
    {
        return $this->requestRaw('GET', "/api/monitor/{$amount}");
    }
}
