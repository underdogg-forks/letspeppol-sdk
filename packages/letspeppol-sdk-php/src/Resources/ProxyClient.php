<?php

namespace LetsPeppolSdk\Resources;

/**
 * Proxy API Client for LetsPeppol document transmission
 */
class ProxyClient extends BaseResource
{
    /**
     * Get all new documents
     */
    public function getAllNewDocuments(int $size = 100): array
    {
        return $this->get('/sapi/document', ['size' => $size]);
    }

    /**
     * Get status updates for specific documents
     */
    public function getStatusUpdates(array $documentIds): array
    {
        return $this->post('/sapi/document/status', $documentIds);
    }

    /**
     * Get document by ID
     */
    public function getDocument(string $id): array
    {
        return $this->get("/sapi/document/{$id}");
    }

    /**
     * Create document to send
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
