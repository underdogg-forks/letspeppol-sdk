# Comparison: Laravel-Embedded vs Standalone Package

This document compares the original Laravel-embedded code with the new standalone package.

## File Organization

### Before (Laravel-Embedded)
```
app/Services/LetsPeppol/
├── BaseClient.php              # Laravel-specific base
├── KycClient.php              # Uses Laravel HTTP facade
├── ProxyClient.php            # Uses Laravel HTTP facade
├── AppClient.php              # Uses Laravel HTTP facade
├── LetsPeppolClient.php       # Wrapper for all clients
└── Examples/
    └── ExampleUsage.php       # Laravel examples
```

### After (Standalone Package)
```
packages/letspeppol-sdk-php/
├── composer.json              # Package manifest
├── README.md                  # Standalone documentation
├── PACKAGE_SUMMARY.md         # Implementation details
├── phpunit.xml.dist          # Test configuration
├── .php-cs-fixer.dist.php    # Code style config
├── .gitignore                # Package-specific ignores
├── src/
│   ├── Session.php           # Guzzle HTTP management
│   ├── LetsPeppolClient.php  # Unified client
│   ├── Exceptions/           # Custom exception hierarchy
│   └── Resources/            # API resource classes
│       ├── BaseResource.php  # Framework-agnostic base
│       ├── KycClient.php     # Pure Guzzle
│       ├── ProxyClient.php   # Pure Guzzle
│       └── AppClient.php     # Pure Guzzle
└── examples/
    ├── README.md
    ├── basic_usage.php       # Standalone examples
    └── complete_workflow.php # Full workflow demo
```

## Code Comparison

### 1. Base Client

#### Before (Laravel)
```php
namespace App\Services\LetsPeppol;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class BaseClient
{
    protected string $baseUrl;
    protected ?string $token = null;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    protected function http(): PendingRequest
    {
        $http = Http::baseUrl($this->baseUrl)
            ->accept('application/json')
            ->timeout(30);

        if ($this->token) {
            $http->withToken($this->token);
        }

        return $http;
    }

    protected function handleResponse($response)
    {
        if ($response->successful()) {
            return $response->json();
        }

        throw new \RuntimeException(
            "API request failed: {$response->status()} - {$response->body()}",
            $response->status()
        );
    }
}
```

#### After (Guzzle)
```php
namespace LetsPeppolSdk\Resources;

use GuzzleHttp\Exception\GuzzleException;
use LetsPeppolSdk\Exceptions\ApiException;
use LetsPeppolSdk\Session;

abstract class BaseResource
{
    protected Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->session->getClient()->request($method, $endpoint, $options);
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($statusCode >= 200 && $statusCode < 300) {
                $decoded = json_decode($body, true);
                return $decoded ?? [];
            }

            $errorData = json_decode($body, true) ?? [];
            throw new ApiException(
                "API request failed: {$statusCode} - {$body}",
                $statusCode,
                $errorData
            );
        } catch (GuzzleException $e) {
            throw new ApiException(
                "Network error: {$e->getMessage()}",
                0,
                [],
                $e
            );
        }
    }
}
```

### 2. KycClient Constructor

#### Before (Laravel)
```php
class KycClient extends BaseClient
{
    public function __construct(?string $baseUrl = null)
    {
        parent::__construct($baseUrl ?? config('services.letspeppol.kyc_url', 'https://kyc.letspeppol.org'));
    }
}
```

#### After (Guzzle)
```php
class KycClient extends BaseResource
{
    // Session is injected, no configuration needed
    // Base URL is configured in Session
}
```

### 3. Authentication Method

#### Before (Laravel)
```php
public function authenticate(string $email, string $password): string
{
    $credentials = base64_encode("{$email}:{$password}");
    
    $response = $this->http()
        ->withHeaders(['Authorization' => "Basic {$credentials}"])
        ->post('/api/jwt/auth');

    if ($response->successful()) {
        $token = $response->body();
        $this->setToken($token);
        return $token;
    }

    throw new \RuntimeException(
        "Authentication failed: {$response->status()} - {$response->body()}",
        $response->status()
    );
}
```

#### After (Guzzle)
```php
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
```

### 4. Unified Client

#### Before (Laravel)
```php
class LetsPeppolClient
{
    protected KycClient $kycClient;
    protected ProxyClient $proxyClient;
    protected AppClient $appClient;

    public function __construct(
        ?string $kycUrl = null,
        ?string $proxyUrl = null,
        ?string $appUrl = null
    ) {
        $this->kycClient = new KycClient($kycUrl);
        $this->proxyClient = new ProxyClient($proxyUrl);
        $this->appClient = new AppClient($appUrl);
    }

    public function setToken(string $token): static
    {
        $this->kycClient->setToken($token);
        $this->proxyClient->setToken($token);
        $this->appClient->setToken($token);
        return $this;
    }
}
```

#### After (Guzzle)
```php
class LetsPeppolClient
{
    protected Session $kycSession;
    protected Session $proxySession;
    protected Session $appSession;

    protected KycClient $kycClient;
    protected ProxyClient $proxyClient;
    protected AppClient $appClient;

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

    public function setToken(string $token): self
    {
        $this->kycSession->setToken($token);
        $this->proxySession->setToken($token);
        $this->appSession->setToken($token);
        return $this;
    }
}
```

## Usage Comparison

### Before (Laravel Application)
```php
// In Laravel controller or service
use App\Services\LetsPeppol\LetsPeppolClient;

$client = new LetsPeppolClient();
$token = $client->authenticate($email, $password);
$company = $client->app()->getCompany();
```

### After (Any PHP Application)
```php
// In any PHP script
require_once 'vendor/autoload.php';

use LetsPeppolSdk\LetsPeppolClient;

$client = new LetsPeppolClient();
$token = $client->authenticate($email, $password);
$company = $client->app()->getCompany();
```

## Installation Comparison

### Before (Laravel)
- Embedded in Laravel application
- No separate installation
- Tied to Laravel version
- Uses Laravel's service container

### After (Standalone)
```bash
# As path repository
composer require letspeppol/letspeppol-sdk-php

# Or future Packagist
composer require letspeppol/letspeppol-sdk-php
```

## Configuration Comparison

### Before (Laravel)
```php
// config/services.php
'letspeppol' => [
    'kyc_url' => env('LETSPEPPOL_KYC_URL', 'https://kyc.letspeppol.org'),
    'proxy_url' => env('LETSPEPPOL_PROXY_URL', 'https://proxy.letspeppol.org'),
    'app_url' => env('LETSPEPPOL_APP_URL', 'https://app.letspeppol.org'),
],
```

### After (Standalone)
```php
// Direct instantiation with defaults
$client = new LetsPeppolClient();

// Or custom URLs
$client = new LetsPeppolClient(
    'https://custom-kyc.example.com',
    'https://custom-proxy.example.com',
    'https://custom-app.example.com'
);
```

## Dependency Comparison

### Before (Laravel)
```json
{
  "require": {
    "php": "^8.4",
    "laravel/framework": "^12.44",
    ...
  }
}
```

### After (Standalone)
```json
{
  "require": {
    "php": "^8.1",
    "guzzlehttp/guzzle": "^7.4.5"
  }
}
```

## Key Advantages of Standalone Package

1. **Framework Independence**
   - Can be used in Symfony, Slim, WordPress, or plain PHP
   - No Laravel dependency overhead

2. **Lower PHP Version Requirement**
   - Before: PHP 8.4+
   - After: PHP 8.1+

3. **Proper Package Structure**
   - Follows Composer best practices
   - Can be published to Packagist
   - Version-able and release-able

4. **Better Exception Handling**
   - Custom exception hierarchy
   - Access to response data
   - Type-specific exceptions

5. **Standalone Documentation**
   - Complete README
   - Usage examples
   - Can be used outside Laravel context

6. **Testing Infrastructure**
   - PHPUnit configuration
   - PHP-CS-Fixer for code quality
   - Independent test suite possible

7. **Reusability**
   - Can be used in multiple projects
   - Easy to share and distribute
   - Follows open-source standards

## Summary

The standalone package maintains **100% feature parity** with the Laravel version while being:
- ✅ Framework-agnostic
- ✅ More portable
- ✅ Better documented
- ✅ Easier to maintain
- ✅ Follows industry standards
- ✅ Ready for distribution

This matches the structure and approach of the reference `freelancer-sdk-php` package, making it a proper SDK that can be used anywhere PHP runs.
