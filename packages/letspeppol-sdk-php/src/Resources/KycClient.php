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
     * Makes a POST request to /api/jwt/auth with Basic authentication.
     *
     * **Request:**
     * - Headers: Authorization: Basic base64(email:password)
     * - No body
     *
     * **Response:**
     * - 200: Returns JWT token as plain text string
     * - 401: Authentication failed
     *
     * **Example:**
     * ```php
     * try {
     *     $token = $client->kyc()->authenticate('user@example.com', 'password123');
     *     echo "Token: $token";
     * } catch (AuthenticationException $e) {
     *     echo "Authentication failed: " . $e->getMessage();
     * }
     * ```
     *
     * @param string $email User email address
     * @param string $password User password
     * @return string JWT token for authenticated requests
     * @throws AuthenticationException When authentication fails (401) or network error occurs
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
     *
     * Retrieves company details from business register by Peppol ID.
     *
     * **Request:**
     * - GET /api/register/company/{peppolId}
     * - No authentication required
     *
     * **Response JSON:**
     * ```json
     * {
     *   "peppolId": "0208:BE0123456789",
     *   "vatNumber": "BE0123456789",
     *   "name": "Company Name BVBA",
     *   "address": {
     *     "street": "Street Name 123",
     *     "city": "Brussels",
     *     "postalCode": "1000",
     *     "country": "BE"
     *   },
     *   "directors": [
     *     {
     *       "id": 123,
     *       "name": "John Doe",
     *       "nationalNumber": "12345678901"
     *     }
     *   ]
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $company = $client->kyc()->getCompany('0208:BE0123456789');
     * echo "Company: " . $company['name'];
     * echo "VAT: " . $company['vatNumber'];
     * ```
     *
     * @param string $peppolId Peppol participant ID (e.g., "0208:BE0123456789")
     * @return array Company information including name, address, directors
     * @throws ApiException When company not found (404) or invalid Peppol ID format
     */
    public function getCompany(string $peppolId): array
    {
        return $this->get("/api/register/company/{$peppolId}");
    }

    /**
     * Confirm company and send verification email (Registration step 2)
     *
     * Confirms company registration and sends verification email to the specified address.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "peppolId": "0208:BE0123456789",
     *   "email": "admin@company.com",
     *   "name": "John Doe",
     *   "password": "SecurePass123!"
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "success": true,
     *   "message": "Verification email sent",
     *   "email": "admin@company.com"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $result = $client->kyc()->confirmCompany([
     *     'peppolId' => '0208:BE0123456789',
     *     'email' => 'admin@company.com',
     *     'name' => 'John Doe',
     *     'password' => 'SecurePass123!'
     * ], 'en');
     * ```
     *
     * @param array $data Company confirmation data (peppolId, email, name, password)
     * @param string|null $language Optional language code for email (e.g., 'en', 'nl', 'fr')
     * @return array Confirmation result with success status
     * @throws ApiException When company already registered (409) or validation fails (422)
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
     *
     * Verifies the token received in the registration email.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "token": "abc123def456..."
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "valid": true,
     *   "peppolId": "0208:BE0123456789",
     *   "email": "admin@company.com"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $result = $client->kyc()->verifyToken($tokenFromEmail);
     * if ($result['valid']) {
     *     echo "Email verified for: " . $result['email'];
     * }
     * ```
     *
     * @param string $token Verification token from email
     * @return array Verification result with validity status
     * @throws ApiException When token is invalid (401) or expired (410)
     */
    public function verifyToken(string $token): array
    {
        return $this->post('/api/register/verify', ['token' => $token]);
    }

    /**
     * Prepare document for signing (Registration step 4)
     *
     * Prepares the registration contract for digital signing with Web eID.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "token": "verification_token_from_email",
     *   "directorId": 123,
     *   "certificate": "base64_encoded_certificate"
     * }
     * ```
     *
     * **Response JSON:**
     * ```json
     * {
     *   "hash": "sha256_hash_of_document",
     *   "hashAlgorithm": "SHA-256",
     *   "documentId": "abc123"
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $result = $client->kyc()->prepareSigning([
     *     'token' => $verificationToken,
     *     'directorId' => 123,
     *     'certificate' => base64_encode($certificateData)
     * ]);
     * $hashToSign = $result['hash'];
     * ```
     *
     * @param array $data Signing preparation data (token, directorId, certificate)
     * @return array Signing preparation result with document hash
     * @throws ApiException When token invalid (401) or certificate invalid (400)
     */
    public function prepareSigning(array $data): array
    {
        return $this->post('/api/identity/sign/prepare', $data);
    }

    /**
     * Get contract PDF (Registration step 5)
     *
     * Downloads the registration contract PDF for review before signing.
     *
     * **Request:**
     * - GET /api/identity/contract/{directorId}?token=verification_token
     *
     * **Response:**
     * - Binary PDF content
     *
     * **Example:**
     * ```php
     * $pdfContent = $client->kyc()->getContract(123, $verificationToken);
     * file_put_contents('contract.pdf', $pdfContent);
     * ```
     *
     * @param int $directorId Director ID from company information
     * @param string $token Verification token from email
     * @return string PDF content as binary string
     * @throws ApiException When contract not found (404) or token invalid (401)
     */
    public function getContract(int $directorId, string $token): string
    {
        return $this->requestRaw('GET', "/api/identity/contract/{$directorId}", [
            'query' => ['token' => $token]
        ]);
    }

    /**
     * Finalize document signing (Registration step 6)
     *
     * Completes registration by submitting the digital signature.
     *
     * **Request JSON:**
     * ```json
     * {
     *   "token": "verification_token_from_email",
     *   "directorId": 123,
     *   "signature": "base64_encoded_digital_signature"
     * }
     * ```
     *
     * **Response:**
     * - Body: Signed PDF content (binary)
     * - Headers:
     *   - Registration-Status: "COMPLETED" or "PENDING"
     *   - Registration-Provider: "WEB_EID" or other provider
     *
     * **Example:**
     * ```php
     * $result = $client->kyc()->finalizeSigning([
     *     'token' => $verificationToken,
     *     'directorId' => 123,
     *     'signature' => base64_encode($signatureData)
     * ]);
     * file_put_contents('signed_contract.pdf', $result['pdf']);
     * echo "Status: " . $result['status'];
     * ```
     *
     * @param array $data Signing finalization data (token, directorId, signature)
     * @return array ['pdf' => string, 'status' => string, 'provider' => string]
     * @throws ApiException When signature invalid (400) or signing fails (500)
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
     *
     * Retrieves full account details for the authenticated user.
     *
     * **Request:**
     * - GET /sapi/company
     * - Requires: JWT token in Authorization header
     *
     * **Response JSON:**
     * ```json
     * {
     *   "id": 456,
     *   "peppolId": "0208:BE0123456789",
     *   "vatNumber": "BE0123456789",
     *   "name": "Company Name BVBA",
     *   "email": "admin@company.com",
     *   "registeredOnPeppol": true,
     *   "balance": 100.00,
     *   "address": {
     *     "street": "Street Name 123",
     *     "city": "Brussels",
     *     "postalCode": "1000",
     *     "country": "BE"
     *   }
     * }
     * ```
     *
     * **Example:**
     * ```php
     * $account = $client->kyc()->getAccountInfo();
     * echo "Company: " . $account['name'];
     * echo "Balance: " . $account['balance'];
     * echo "Peppol Status: " . ($account['registeredOnPeppol'] ? 'Registered' : 'Not registered');
     * ```
     *
     * @return array Account information including company details, balance, and status
     * @throws ApiException When not authenticated (401)
     */
    public function getAccountInfo(): array
    {
        return $this->get('/sapi/company');
    }

    /**
     * Search companies
     *
     * Search for companies by VAT number, Peppol ID, or name.
     *
     * **Request:**
     * - GET /sapi/company/search?vatNumber=...&peppolId=...&companyName=...
     * - At least one parameter required
     *
     * **Response JSON:**
     * ```json
     * [
     *   {
     *     "peppolId": "0208:BE0123456789",
     *     "vatNumber": "BE0123456789",
     *     "name": "Company Name BVBA",
     *     "country": "BE"
     *   }
     * ]
     * ```
     *
     * **Example:**
     * ```php
     * // Search by VAT number
     * $companies = $client->kyc()->searchCompanies(vatNumber: 'BE0123456789');
     *
     * // Search by Peppol ID
     * $companies = $client->kyc()->searchCompanies(peppolId: '0208:BE0123456789');
     *
     * // Search by name
     * $companies = $client->kyc()->searchCompanies(companyName: 'ACME Corporation');
     * ```
     *
     * @param string|null $vatNumber VAT number to search for
     * @param string|null $peppolId Peppol ID to search for
     * @param string|null $companyName Company name to search for (partial match)
     * @return array Array of matching companies
     * @throws ApiException When no search criteria provided (400)
     */
    public function searchCompanies(?string $vatNumber = null, ?string $peppolId = null, ?string $companyName = null): array
    {
        $params = array_filter([
            'vatNumber' => $vatNumber,
            'peppolId' => $peppolId,
            'companyName' => $companyName,
        ], function ($value) {
            return $value !== null;
        });

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
