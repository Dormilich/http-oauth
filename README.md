# Client Credentials OAuth2 extension

This is an OAuth2 extension of (and including) `dormilich/http-client` for use with the
[Client Credentials](https://datatracker.ietf.org/doc/html/rfc6749#section-4.4) authorisation scheme.

The primary use of this is backend API communication where the resource consumer (backend service)
is a trusted client of the resource owner (by having granted permanent credentials for the resource).

## Installation

You can install this library via composer:
```
composer require dormilich/http-oauth
```
To use this library, install you personal choice of a [PSR-16](https://www.php-fig.org/psr/psr-16/)
cache. Additionally, the parent library requires a PSR-18 HTTP client and PSR-17 HTTP factories.

## Configuration

### HTTP client configuration

This configuration is described in the respective project.

### Cache configuration

There is no project-specific configuration for the cache component.

### OAuth configuration

Since OAuth2 may result in pre-flight requests, it needs an HTTP client configured.
```php
use Dormilich\HttpClient\Client;
use Dormilich\HttpClient\Transformer\JsonDecoder;
use Dormilich\HttpClient\Transformer\JsonEncoder;
use Dormilich\HttpOauth\TokenClient;
use Dormilich\HttpOauth\TokenProvider;
use Dormilich\HttpOauth\Credentials\ClientCredentials;
use Dormilich\HttpOauth\Credentials\DefaultProvider;
use Dormilich\HttpOauth\Encoder\AuthorisationEncoder;

// replace this with the actual implementations
$httpClient = new HttpClient();         // PSR-18
$requestFactory = new RequestFactory(); // PSR-17
$streamFactory = new StreamFactory();   // PSR-17
$simpleCache = new SimpleCache();       // PSR-16

// define your OAuth credentials
$credentials = new ClientCredentials('<client-id>', '<client-secret>', '<authorisation-url>');
// use a credentials provider
$provider = new DefaultProvider($credentials);

// set up the extension
# the token client is responsible for making the authorisation requests
$tokenClient = new TokenClient($provider, $httpClient, $requestFactory, $streamFactory);
# the token provider is a Facade for getting the OAuth token
# either from a persistence layer or the authorisation server
$tokenProvider = new TokenProvider($tokenClient, $simpleCache);
# the request processor for the HTTP client 
$authorisation = new AuthorisationEncoder($tokenProvider);

// set up the HTTP client
$client = new Client($httpClient, $requestFactory, $streamFactory);
// add OAuth extension
$client->addEncoder($authorisation);
// add more encoders/decoders as necessary, e.g.
$client->addTransformer(new JsonEncoder());
$client->addTransformer(new JsonEncoder());
```
As per [OAuth specification](https://datatracker.ietf.org/doc/html/rfc6749), the token does not
need to have an expiration defined. In that case the token may become stale and the resource
request may fail with a 403 Unauthorized response (or similar). If a token is found expired,
the extension will fetch a new token before attempting the resource request.

## Credentials

The extension supports the use of multiple OAuth credentials within the same HTTP client.

The predefined credentials providers are:
- `DefaultProvider`: Returns the same credentials for every resource request.
- `DomainProvider`: Returns credentials based on the (partial) domain of the resource request URL.
- `ChainProvider`: Aggregator for multiple providers.

If no credentials are found for the resource request URL, the authorisation header is not added to
the resource request.

Examples:
```php
use Dormilich\HttpOauth\Credentials\ChainProvider;
use Dormilich\HttpOauth\Credentials\ClientCredentials;
use Dormilich\HttpOauth\Credentials\DefaultProvider;
use Dormilich\HttpOauth\Credentials\DomainProvider;

$credentials = new ClientCredentials('<client-id>', '<client-secret>', '<outhorisation-url>');

$default = new DefaultProvider($credentials);

// return credentials when requesting authorisation for "example.com" and
// its subdomains like "api.example.com" as well as "api.example.org" (etc.)
$domain = new DomainProvider();
$domain->add($credentials, ['example.com', 'api.example.org']);

$chain = new ChainProvider([$domain, $default]);
```
