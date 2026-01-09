<?php

namespace LetsPeppolSdk\Resources;

use LetsPeppolSdk\Exceptions\AuthenticationException;
use LetsPeppolSdk\Exceptions\ApiException;

/**
 * KYC API Client for LetsPeppol authentication and registration
 */
class KycClient extends BaseResource
{
    /**
     * Authenticate and get JWT token
     * 
     * @param string $email User email
     * @param string $password User password
     * @return string JWT token
     * @throws AuthenticationException
     */
    public function authenticate(string $email, string $password): string
    {
        $credentials = base64_encode("{$email}:{$password}");
        
        try {
            $response = $this->session->getClient()->request('POST', '/api/jwt/auth', [
                'headers' => [
                    'Authorization' => "Basic {$credentials}",
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($statusCode === 200) {
                $token = $body;
                $this->session->setToken($token);
                return $token;
            }

            throw new AuthenticationException(
                "Authentication failed: {$statusCode} - {$body}",
                $statusCode
            );
        } catch (\Exception $e) {
            if ($e instanceof AuthenticationException) {
                throw $e;
            }
            throw new AuthenticationException("Authentication error: {$e->getMessage()}", 0, [], $e);
        }
    }

    /**
     * Get company information by Peppol ID (Registration step 1)
     */
    public function getCompany(string $peppolId): array
    {
        return $this->get("/api/register/company/{$peppolId}");
    }

    /**
     * Confirm company and send verification email (Registration step 2)
     */
    public function confirmCompany(array $data, ?string $language = null): array
    {
        $options = ['json' => $data];
        
        if ($language) {
            $options['headers'] = ['Accept-Language' => $language];
        }

        return $this->request('POST', '/api/register/confirm-company', $options);
    }

    /**
     * Verify email token (Registration step 3)
     */
    public function verifyToken(string $token): array
    {
        return $this->post('/api/register/verify', ['token' => $token]);
    }

    /**
     * Prepare document for signing (Registration step 4)
     */
    public function prepareSigning(array $data): array
    {
        return $this->post('/api/identity/sign/prepare', $data);
    }

    /**
     * Get contract PDF (Registration step 5)
     */
    public function getContract(int $directorId, string $token): string
    {
        return $this->requestRaw('GET', "/api/identity/contract/{$directorId}", [
            'query' => ['token' => $token]
        ]);
    }

    /**
     * Finalize document signing (Registration step 6)
     */
    public function finalizeSigning(array $data): array
    {
        $result = $this->requestWithHeaders('POST', '/api/identity/sign/finalize', [
            'json' => $data
        ]);

        return [
            'pdf' => $result['body'],
            'status' => $result['headers']['Registration-Status'] ?? '',
            'provider' => $result['headers']['Registration-Provider'] ?? '',
        ];
    }

    /**
     * Get account information (requires JWT token)
     */
    public function getAccountInfo(): array
    {
        return $this->get('/sapi/company');
    }

    /**
     * Search companies
     */
    public function searchCompanies(?string $vatNumber = null, ?string $peppolId = null, ?string $companyName = null): array
    {
        $params = array_filter([
            'vatNumber' => $vatNumber,
            'peppolId' => $peppolId,
            'companyName' => $companyName,
        ], fn($value) => $value !== null);

        return $this->get('/sapi/company/search', $params);
    }

    /**
     * Register on Peppol Directory
     */
    public function registerPeppol(): array
    {
        $result = $this->requestWithHeaders('POST', '/sapi/company/peppol/register');
        
        if ($result['body']) {
            $newToken = $result['body'];
            $this->session->setToken($newToken);
            return ['token' => $newToken, 'status' => 'updated'];
        }
        
        return ['status' => 'already_registered'];
    }

    /**
     * Unregister from Peppol Directory
     */
    public function unregisterPeppol(): array
    {
        $result = $this->requestWithHeaders('POST', '/sapi/company/peppol/unregister');
        
        if ($result['body']) {
            $newToken = $result['body'];
            $this->session->setToken($newToken);
            return ['token' => $newToken, 'status' => 'updated'];
        }
        
        return ['status' => 'already_unregistered'];
    }

    /**
     * Download signed contract
     */
    public function getSignedContract(): string
    {
        return $this->requestRaw('GET', '/sapi/company/signed-contract');
    }

    /**
     * Request password reset
     */
    public function forgotPassword(string $email, ?string $language = null): void
    {
        $options = ['json' => ['email' => $email]];
        
        if ($language) {
            $options['headers'] = ['Accept-Language' => $language];
        }

        $this->request('POST', '/api/password/forgot', $options);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        $this->post('/api/password/reset', [
            'token' => $token,
            'newPassword' => $newPassword,
        ]);
    }

    /**
     * Change password (requires authentication)
     */
    public function changePassword(string $oldPassword, string $newPassword): void
    {
        $this->post('/sapi/password/change', [
            'oldPassword' => $oldPassword,
            'newPassword' => $newPassword,
        ]);
    }
}
