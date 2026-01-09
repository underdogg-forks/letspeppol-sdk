<?php

namespace LetsPeppolSdk\Resources;

/**
 * App API Client for LetsPeppol application management
 */
class AppClient extends BaseResource
{
    // ==================== Documents ====================

    /**
     * Validate UBL XML
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
     */
    public function getDocument(string $id): array
    {
        return $this->get("/sapi/document/{$id}");
    }

    /**
     * Create document from UBL XML
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
     */
    public function updateDocument(string $id, string $ublXml, bool $draft = false, ?string $schedule = null): array
    {
        $params = ['draft' => $draft ? 'true' : 'false'];
        if ($schedule) {
            $params['schedule'] = $schedule;
        }

        return $this->request('PUT', "/sapi/document/{$id}", [
            'body' => $ublXml,
            'headers' => ['Content-Type' => 'text/xml'],
            'query' => $params,
        ]);
    }

    /**
     * Send document
     */
    public function sendDocument(string $id, ?string $schedule = null): array
    {
        $params = [];
        if ($schedule) {
            $params['schedule'] = $schedule;
        }

        return $this->request('PUT', "/sapi/document/{$id}/send", [
            'query' => $params
        ]);
    }

    /**
     * Mark document as read
     */
    public function markDocumentRead(string $id): array
    {
        return $this->request('PUT', "/sapi/document/{$id}/read");
    }

    /**
     * Mark document as paid
     */
    public function markDocumentPaid(string $id): array
    {
        return $this->request('PUT', "/sapi/document/{$id}/paid");
    }

    /**
     * Delete document
     */
    public function deleteDocument(string $id): void
    {
        $this->delete("/sapi/document/{$id}");
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
        return $this->put("/sapi/partner/{$id}", $partnerData);
    }

    /**
     * Delete partner
     */
    public function deletePartner(int $id): void
    {
        $this->delete("/sapi/partner/{$id}");
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
        return $this->put("/sapi/product/{$id}", $productData);
    }

    /**
     * Delete product
     */
    public function deleteProduct(int $id): void
    {
        $this->delete("/sapi/product/{$id}");
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
        return $this->get("/sapi/product-category/{$id}", ['deep' => $deep ? 'true' : 'false']);
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
        return $this->put("/sapi/product-category/{$id}", $categoryData);
    }

    /**
     * Delete category
     */
    public function deleteCategory(int $id): void
    {
        $this->delete("/sapi/product-category/{$id}");
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
