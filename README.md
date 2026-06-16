# ip-api-io/ipapi-php — Official PHP client for [ip-api.io](https://ip-api.io)

[![Packagist](https://img.shields.io/packagist/v/ip-api-io/ipapi-php)](https://packagist.org/packages/ip-api-io/ipapi-php)
[![test](https://github.com/ip-api-io/ipapi-php/actions/workflows/test.yml/badge.svg)](https://github.com/ip-api-io/ipapi-php/actions/workflows/test.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

The official PHP client for the [ip-api.io](https://ip-api.io) IP intelligence
platform. One client covers [IP geolocation](https://ip-api.io/what-is-my-ip),
[email validation](https://ip-api.io/email-validation) and [verification](https://ip-api.io/email-verification-api)
(syntax, MX, SMTP deliverability), [fraud detection](https://ip-api.io/fraud-detection-api)
and [risk scoring](https://ip-api.io/risk-score),
[VPN](https://ip-api.io/vpn-detection-api)/[proxy](https://ip-api.io/proxy-detection-api)/[Tor detection](https://ip-api.io/tor-detection),
[disposable email detection](https://ip-api.io/disposable-email-checker), [ASN lookup](https://ip-api.io/asn-lookup),
[WHOIS](https://ip-api.io/whois-lookup), [reverse DNS](https://ip-api.io/reverse-dns-lookup),
[MX records](https://ip-api.io/mx-record-lookup) and [domain age](https://ip-api.io/domain-age-checker).

Zero Composer dependencies — just `ext-curl` and `ext-json`.

## Install

```bash
composer require ip-api-io/ipapi-php
```

## Quickstart

```php
use IpApiIo\Client;

$client = new Client(apiKey: 'YOUR_API_KEY'); // free key at https://ip-api.io

// Where is this IP, and is it risky?
$info = $client->lookup('8.8.8.8');
echo $info['location']['country'];               // "United States"
var_dump($info['suspicious_factors']['is_vpn']);  // false

$risk = $client->riskScore('8.8.8.8');
echo "{$risk['score']} {$risk['risk_level']}";    // 0 low

$email = $client->validateEmail('user@example.com');
echo $email['reachable'];                          // "yes"
```

An API key is required — the API rejects keyless requests with `401`. Sign up at
[ip-api.io](https://ip-api.io) for a free key.

## Documentation

Each guide documents the methods for one capability, with runnable examples and a link
to the matching ip-api.io product page:

- **[IP geolocation & bulk lookup](docs/ip-geolocation.md)** — `lookup`, `lookupBatch`
- **[Email validation & verification](docs/email-validation.md)** — `emailInfo`, `validateEmail`, `validateEmailBatch`
- **[Fraud detection & risk scoring](docs/fraud-risk-scoring.md)** — `riskScore`, `emailRiskScore`, `ipReputation`
- **[VPN, proxy & Tor detection](docs/vpn-proxy-tor.md)** — `torCheck`, `suspicious_factors`
- **[ASN & DNS lookups](docs/asn-and-dns.md)** — `asn`, `whois`, `reverseDns`, `forwardDns`, `mxRecords`
- **[Domain age checker](docs/domain-age.md)** — `domainAge`, `domainAgeBatch`
- **[Errors, rate limits & usage](docs/error-handling.md)** — exception types, `rateLimit`, `usageSummary`

## Methods

Every method maps to one ip-api.io endpoint and its product page:

| Method | Endpoint | Product page |
|---|---|---|
| `lookup(?string $ip = null)` | `GET /api/v1/ip[/{ip}]` | [IP geolocation](https://ip-api.io/what-is-my-ip) |
| `lookupBatch(array $ips)` | `POST /api/v1/ip/batch` (≤100 IPs) | [Bulk IP lookup](https://ip-api.io/bulk-ip-lookup) |
| `emailInfo(string $email)` | `GET /api/v1/email/{email}` | [Email validation](https://ip-api.io/email-validation) |
| `validateEmail(string $email)` | `GET /api/v1/email/advanced/{email}` | [Advanced email validation](https://ip-api.io/advanced-email-validation) |
| `validateEmailBatch(array $emails)` | `POST /api/v1/email/advanced/batch` (≤100) | [Email list cleaning](https://ip-api.io/email-list-cleaning) |
| `riskScore(?string $ip = null)` | `GET /api/v1/risk-score[/{ip}]` | [Risk score](https://ip-api.io/risk-score) |
| `emailRiskScore(string $email)` | `GET /api/v1/risk-score/email/{email}` | [Fraud detection](https://ip-api.io/fraud-detection-api) |
| `ipReputation(string $ip)` | `GET /api/v1/ip-reputation/{ip}` | [IP reputation](https://ip-api.io/ip-reputation) |
| `torCheck(string $ip)` | `GET /api/v1/tor/{ip}` | [Tor detection](https://ip-api.io/tor-detection) |
| `asn(string $ip)` | `GET /api/v1/asn/{ip}` | [ASN lookup](https://ip-api.io/asn-lookup) |
| `whois(string $domain)` | `GET /api/v1/dns/whois/{domain}` | [WHOIS lookup](https://ip-api.io/whois-lookup) |
| `reverseDns(string $ip)` | `GET /api/v1/dns/reverse/{ip}` | [Reverse DNS](https://ip-api.io/reverse-dns-lookup) |
| `forwardDns(string $hostname)` | `GET /api/v1/dns/forward/{hostname}` | — |
| `mxRecords(string $domain)` | `GET /api/v1/dns/mx/{domain}` | [MX record lookup](https://ip-api.io/mx-record-lookup) |
| `domainAge(string $domain)` | `GET /api/v1/domain/age/{domain}` | [Domain age checker](https://ip-api.io/domain-age-checker) |
| `domainAgeBatch(array $domains)` | `POST /api/v1/domain/age/batch` | [Domain age checker](https://ip-api.io/domain-age-checker) |
| `rateLimit()` | `GET /api/v1/ratelimit` | — |
| `usageSummary()` | `GET /api/v1/usage/summary` | — |

All methods return parsed JSON as associative arrays.

## Error handling

The client throws typed exceptions and **never retries** — on `429`,
`RateLimitError::$reset` tells you when your quota renews:

```php
use IpApiIo\AuthenticationError;
use IpApiIo\RateLimitError;

try {
    $client->lookup('8.8.8.8');
} catch (RateLimitError $e) {
    echo "limit={$e->limit} remaining={$e->remaining} resets_at={$e->reset}";
} catch (AuthenticationError $e) {
    echo 'invalid API key';
}
```

See [docs/error-handling.md](docs/error-handling.md) for the full exception taxonomy.

## Links

- Full tutorial: https://ip-api.io/docs/sdk/php
- Website: https://ip-api.io
- API reference: https://ip-api.io/api-docs.html
- OpenAPI spec: https://ip-api.io/openapi.json
- Get a free API key: https://ip-api.io

---

`ip-api-io/ipapi-php` is the official client for [ip-api.io](https://ip-api.io).
It is not affiliated with ip-api.com or ipapi.com.
