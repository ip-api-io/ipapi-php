# Errors, rate limits & usage

The client throws a typed exception for every HTTP failure and **never retries** — you
stay in control of back-off. It also exposes your current quota so you can throttle
before you hit a limit.

## Exception taxonomy

Every exception extends `IpApiIo\IpApiError` (a `RuntimeException`), which carries
`$statusCode` and the raw response `$body`. Catch the specific subclass you care about:

| Exception | HTTP status | Meaning |
|---|---|---|
| `IpApiIo\AuthenticationError` | 401, 403 | Missing or invalid API key |
| `IpApiIo\RateLimitError` | 429 | Quota exhausted (see below) |
| `IpApiIo\InvalidRequestError` | 400, 404, 422 | Malformed input or unknown resource |
| `IpApiIo\ServerError` | 5xx | ip-api.io server-side failure |
| `IpApiIo\IpApiError` | other | Base / fallback (also thrown on transport errors) |

```php
use IpApiIo\Client;
use IpApiIo\IpApiError;
use IpApiIo\AuthenticationError;
use IpApiIo\RateLimitError;
use IpApiIo\InvalidRequestError;
use IpApiIo\ServerError;

$client = new Client(apiKey: 'YOUR_API_KEY');

try {
    $info = $client->lookup('8.8.8.8');
    echo $info['location']['country'];
} catch (RateLimitError $e) {
    echo "quota hit — resets at {$e->reset}";
} catch (AuthenticationError $e) {
    echo 'check your API key';
} catch (InvalidRequestError $e) {
    echo "bad request: {$e->getMessage()}";
} catch (ServerError $e) {
    echo 'ip-api.io is having trouble, try later';
} catch (IpApiError $e) {
    echo "error {$e->statusCode}: {$e->getMessage()}";
}
```

Transport failures (DNS, connection, timeout) are wrapped in `IpApiError` with a
`transport error:` message and no `$statusCode`.

## Rate limits

On HTTP 429 the client throws `RateLimitError`, parsed from the `x-ratelimit-*`
response headers. Because the client never retries, **`$reset` tells you when to**:

```php
try {
    $client->lookup('8.8.8.8');
} catch (RateLimitError $e) {
    echo $e->limit;      // your quota for the window
    echo $e->remaining;  // requests left (0 here)
    echo $e->reset;      // unix timestamp when quota renews
    $wait = ($e->reset ?? 0) - time();
    // schedule a retry after $wait seconds instead of hammering the API
}
```

## `rateLimit()` — check quota proactively

Read your current limits without triggering a 429, so you can throttle in advance.

```php
$rl = $client->rateLimit();

echo $rl['plan_name'];
echo "{$rl['ip_api']['remaining']} / {$rl['ip_api']['limit']}";
echo "{$rl['email_api']['usage_percent']} % used";
echo $rl['next_renewal_date'];
```

`RateLimitInfo`: `plan_id`, `plan_name`, `ip_api` and `email_api`
(`limit`, `remaining`, `used`, `usage_percent`), `interval_seconds`,
`next_renewal_date`, `status`.

## `usageSummary()` — account usage

Aggregate usage for the current period — handy for dashboards and internal alerts.

```php
$usage = $client->usageSummary();

echo "{$usage['totalRequests']} {$usage['successfulRequests']}";
echo "{$usage['rateLimitedRequests']} {$usage['quotaConsumed']}";
echo "{$usage['periodStart']} → {$usage['periodEnd']}";
```

`UsageSummary`: `apiKey`, `apiType`, `periodStart`, `periodEnd`, `totalRequests`,
`successfulRequests`, `rateLimitedRequests`, `quotaConsumed`, `batchOperations`,
`avgRequestDurationMs`.

## See also

- [IP geolocation & bulk lookup](ip-geolocation.md) — the most common call
- API reference: https://ip-api.io/api-docs.html
- Get a free API key: https://ip-api.io
- [Full tutorial on ip-api.io](https://ip-api.io/docs/sdk/php/error-handling)
