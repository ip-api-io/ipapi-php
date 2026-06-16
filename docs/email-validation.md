# Email validation & verification

Check whether an email address is real, deliverable and safe to accept — before it
ever enters your database. The SDK exposes three levels: a fast syntax/MX/disposable
check, full SMTP verification, and a batch endpoint for cleaning whole lists.

Powers [email validation](https://ip-api.io/email-validation),
[advanced email validation](https://ip-api.io/advanced-email-validation),
[email verification](https://ip-api.io/email-verification-api),
[disposable email detection](https://ip-api.io/disposable-email-checker) and
[email list cleaning](https://ip-api.io/email-list-cleaning).

## `emailInfo(string $email)` — fast syntax, MX & disposable check

A lightweight check (no SMTP probe): validates syntax, confirms the domain has MX
records, and flags disposable/throwaway providers. Use it inline on sign-up forms.

```php
use IpApiIo\Client;

$client = new Client(apiKey: 'YOUR_API_KEY');

$info = $client->emailInfo('user@example.com');

var_dump($info['syntax']['is_valid']); // true
var_dump($info['is_disposable']);      // false
var_dump($info['has_mx_records']);     // true
echo $info['mx_records'][0]['hostname'];
```

### Response (`EmailInfo`)

| Field | Type | Description |
|---|---|---|
| `email` | string | The address checked |
| `is_disposable` | bool | Throwaway / temporary provider |
| `has_mx_records` | bool | Domain can receive mail |
| `mx_records` | array | Each: `priority`, `hostname`, `ttl` |
| `syntax` | array | `is_valid`, `domain`, `username`, `error_reasons` |

## `validateEmail(string $email)` — full SMTP deliverability

Advanced verification that connects to the mail server to confirm the mailbox is
deliverable, and adds role-account, free-provider, catch-all and Gravatar signals.
Use it before sending important mail or accepting a paying customer.

```php
$result = $client->validateEmail('user@example.com');

echo $result['reachable'];              // "yes" | "no" | "unknown"
var_dump($result['smtp']['deliverable']); // true
var_dump($result['smtp']['catch_all']);   // false
var_dump($result['disposable']);          // false
var_dump($result['role_account']);        // false  (e.g. info@, support@)
var_dump($result['free']);                // false  (e.g. gmail.com)
echo $result['suggestion'] ?? '';         // typo fix, e.g. "user@gmail.com"
```

### Response (`AdvancedEmailValidation`)

| Field | Type | Description |
|---|---|---|
| `email` | string | The address checked |
| `reachable` | string | `"yes"`, `"no"` or `"unknown"` |
| `syntax` | array | `username`, `domain`, `valid` |
| `smtp` | array\|null | `host_exists`, `deliverable`, `full_inbox`, `catch_all`, `disabled` |
| `gravatar` | array\|null | `has_gravatar`, `gravatar_url` |
| `suggestion` | string | Suggested correction for a likely typo |
| `disposable` | bool | Throwaway provider |
| `role_account` | bool | Role address (info@, sales@, …) |
| `free` | bool | Free webmail provider |
| `has_mx_records` | bool | Domain can receive mail |

## `validateEmailBatch(array $emails)` — clean a list (≤100)

Advanced-validate up to 100 addresses in one request — the building block for
[email list cleaning](https://ip-api.io/email-list-cleaning). Throws
`InvalidArgumentException` if the array is empty or longer than 100.

```php
$batch = $client->validateEmailBatch([
    'user@example.com',
    'fake@mailinator.com',
    'info@example.org',
]);

echo $batch['totalProcessed'];        // 3
echo $batch['successfulValidations']; // 3

foreach ($batch['results'] as $email => $result) {
    echo "{$email} {$result['reachable']}";
}
```

### Response (`BatchEmailValidationResponse`)

| Field | Type | Description |
|---|---|---|
| `results` | array | Map of email → result array |
| `totalProcessed` | int | Emails received |
| `successfulValidations` | int | Emails validated |
| `failedValidations` | int | Emails that errored |

## See also

- [Fraud detection & risk scoring](fraud-risk-scoring.md) — `emailRiskScore` for a 0–100 score
- [ASN & DNS lookups](asn-and-dns.md) — `mxRecords` to inspect a domain's mail servers
- Product pages: [Email validation](https://ip-api.io/email-validation) · [Advanced validation](https://ip-api.io/advanced-email-validation) · [Email verification API](https://ip-api.io/email-verification-api) · [Disposable email checker](https://ip-api.io/disposable-email-checker) · [Email list cleaning](https://ip-api.io/email-list-cleaning)
- [Full tutorial on ip-api.io](https://ip-api.io/docs/sdk/php/email-validation)
