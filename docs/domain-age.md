# Domain age checker

Newly registered domains are a strong fraud and spam signal. `domainAge` returns how
long ago a domain was registered, derived from WHOIS data, so you can flag or block
domains created days ago.

Powers the [domain age checker](https://ip-api.io/domain-age-checker).

## `domainAge(string $domain)` — age of one domain

```php
use IpApiIo\Client;

$client = new Client(apiKey: 'YOUR_API_KEY');

$age = $client->domainAge('example.com');

var_dump($age['is_valid']);       // true
echo $age['registration_date'];   // "1995-08-14"
echo $age['age_in_years'];        // 30
echo $age['age_in_days'];         // 11000+

if (($age['age_in_days'] ?? PHP_INT_MAX) < 30) {
    // treat brand-new domains as higher risk
}
```

### Response (`DomainAge`)

| Field | Type | Description |
|---|---|---|
| `domain` | string | The domain checked |
| `is_valid` | bool | Whether age could be determined |
| `registration_date` | string\|null | First registration date |
| `age_in_years` | int\|null | Age in whole years |
| `age_in_days` | int\|null | Age in days |
| `error` | string\|null | Reason when `is_valid` is false |

## `domainAgeBatch(array $domains)` — many domains at once

Check an array of domains in one request (non-empty; throws
`InvalidArgumentException` if empty).

```php
$batch = $client->domainAgeBatch([
    'example.com',
    'brand-new-domain.xyz',
]);

foreach ($batch['results'] as $domain => $age) {
    echo "{$domain} {$age['age_in_days']}";
}
```

### Response (`BatchDomainAgeResponse`)
`results` — an array mapping each domain to its `DomainAge`.

## See also

- [ASN & DNS lookups](asn-and-dns.md) — `whois` for the full registration record
- [Fraud detection & risk scoring](fraud-risk-scoring.md) — combine age with other signals
- Product page: [Domain age checker](https://ip-api.io/domain-age-checker)
- [Full tutorial on ip-api.io](https://ip-api.io/docs/sdk/php/domain-age)
