<?php

namespace LetsPeppolSdk;

use LetsPeppolSdk\Resources\KycClient;
use LetsPeppolSdk\Resources\ProxyClient;
use LetsPeppolSdk\Resources\AppClient;

/**
 * Unified LetsPeppol API Client
 * 
 * Provides access to all LetsPeppol API modules:
 * - KYC: Authentication and registration
 * - Proxy: Document transmission and registry
 * - App: Document management and business logic
 */
class LetsPeppolClient
{
    protected Session $kycSession;
    protected Session $proxySession;
    protected Session $appSession;

    protected KycClient $kycClient;
    protected ProxyClient $proxyClient;
    protected AppClient $appClient;

    /**
     * Create a new unified LetsPeppol client
     *
     * @param string $kycUrl KYC API base URL
     * @param string $proxyUrl Proxy API base URL
     * @param string $appUrl App API base URL
     * @param string|null $token Optional JWT token for authenticated requests
     */
    public function __construct(
        string $kycUrl = 'https://kyc.letspeppol.org',
        string $proxyUrl = 'https://proxy.letspeppol.org',
        string $appUrl = 'https://app.letspeppol.org',
        ?string $token = null
    ) {
        $this->kycSession = new Session($kycUrl, $token);
        $this->proxySession = new Session($proxyUrl, $token);
        $this->appSession = new Session($appUrl, $token);

        $this->kycClient = new KycClient($this->kycSession);
        $this->proxyClient = new ProxyClient($this->proxySession);
        $this->appClient = new AppClient($this->appSession);
    }

    /**
     * Get KYC client
     */
    public function kyc(): KycClient
    {
        return $this->kycClient;
    }

    /**
     * Get Proxy client
     */
    public function proxy(): ProxyClient
    {
        return $this->proxyClient;
    }

    /**
     * Get App client
     */
    public function app(): AppClient
    {
        return $this->appClient;
    }

    /**
     * Set JWT token for all clients
     *
     * @param string $token JWT token
     * @return self
     */
    public function setToken(string $token): self
    {
        $this->kycSession->setToken($token);
        $this->proxySession->setToken($token);
        $this->appSession->setToken($token);
        return $this;
    }

    /**
     * Get the current JWT token
     *
     * @return string|null Current JWT token or null if not set
     */
    public function getToken(): ?string
    {
        return $this->kycSession->getToken();
    }

    /**
     * Authenticate and set token for all clients
     *
     * Note: This method calls kycClient->authenticate() which internally sets
     * the token on kycSession, then calls setToken() to ensure all sessions
     * (kyc, proxy, app) have the same token for consistency.
     *
     * @param string $email User email
     * @param string $password User password
     * @return string JWT token
     * @throws \LetsPeppolSdk\Exceptions\AuthenticationException
     */
    public function authenticate(string $email, string $password): string
    {
        $token = $this->kycClient->authenticate($email, $password);
        $this->setToken($token);
        return $token;
    }

    /**
     * Create a new instance with a specific token
     */
    public static function withToken(
        string $token,
        string $kycUrl = 'https://kyc.letspeppol.org',
        string $proxyUrl = 'https://proxy.letspeppol.org',
        string $appUrl = 'https://app.letspeppol.org'
    ): self {
        return new self($kycUrl, $proxyUrl, $appUrl, $token);
    }
}
