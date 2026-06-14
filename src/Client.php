<?php

declare(strict_types=1);

namespace IpApiIo;

/**
 * Client for the ip-api.io IP intelligence and email validation API.
 *
 *     $client = new \IpApiIo\Client(apiKey: 'YOUR_API_KEY');
 *     $info = $client->lookup('8.8.8.8');
 *
 * An API key is required by the live API — get a free key at https://ip-api.io.
 */
class Client
{
    public const VERSION = '1.0.0';
    public const MAX_BATCH_SIZE = 100;
    private const USER_AGENT = 'ip-api-io-php/' . self::VERSION;

    private string $baseUrl;

    public function __construct(
        private readonly ?string $apiKey = null,
        string $baseUrl = 'https://ip-api.io',
        private readonly float $timeout = 10.0,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    // -- IP intelligence ------------------------------------------------------

    /**
     * Geolocation + threat intelligence for an IP (or the caller's IP when null).
     *
     * @return array<string, mixed>
     */
    public function lookup(?string $ip = null): array
    {
        return $this->request('GET', $ip !== null ? '/api/v1/ip/' . rawurlencode($ip) : '/api/v1/ip');
    }

    /**
     * Look up to 100 IP addresses in a single request.
     *
     * @param list<string> $ips
     * @return array<string, mixed>
     */
    public function lookupBatch(array $ips): array
    {
        $this->checkBatch($ips, 'ips');

        return $this->request('POST', '/api/v1/ip/batch', ['ips' => array_values($ips)]);
    }

    /** @return array<string, mixed> */
    public function ipReputation(string $ip): array
    {
        return $this->request('GET', '/api/v1/ip-reputation/' . rawurlencode($ip));
    }

    /** @return array<string, mixed> */
    public function torCheck(string $ip): array
    {
        return $this->request('GET', '/api/v1/tor/' . rawurlencode($ip));
    }

    /** @return array<string, mixed> */
    public function asn(string $ip): array
    {
        return $this->request('GET', '/api/v1/asn/' . rawurlencode($ip));
    }

    // -- Email validation -------------------------------------------------------

    /**
     * Syntax, disposability and MX analysis of an email address.
     *
     * @return array<string, mixed>
     */
    public function emailInfo(string $email): array
    {
        return $this->request('GET', '/api/v1/email/' . rawurlencode($email));
    }

    /**
     * Advanced validation including SMTP deliverability checks.
     *
     * @return array<string, mixed>
     */
    public function validateEmail(string $email): array
    {
        return $this->request('GET', '/api/v1/email/advanced/' . rawurlencode($email));
    }

    /**
     * Advanced-validate up to 100 email addresses in a single request.
     *
     * @param list<string> $emails
     * @return array<string, mixed>
     */
    public function validateEmailBatch(array $emails): array
    {
        $this->checkBatch($emails, 'emails');

        return $this->request('POST', '/api/v1/email/advanced/batch', ['emails' => array_values($emails)]);
    }

    // -- Risk scoring -----------------------------------------------------------

    /**
     * Fraud risk score for an IP (or the caller's IP when null).
     *
     * @return array<string, mixed>
     */
    public function riskScore(?string $ip = null): array
    {
        return $this->request(
            'GET',
            $ip !== null ? '/api/v1/risk-score/' . rawurlencode($ip) : '/api/v1/risk-score',
        );
    }

    /** @return array<string, mixed> */
    public function emailRiskScore(string $email): array
    {
        return $this->request('GET', '/api/v1/risk-score/email/' . rawurlencode($email));
    }

    // -- DNS & domains ----------------------------------------------------------

    /** @return array<string, mixed> */
    public function whois(string $domain): array
    {
        return $this->request('GET', '/api/v1/dns/whois/' . rawurlencode($domain));
    }

    /** @return array<string, mixed> */
    public function reverseDns(string $ip): array
    {
        return $this->request('GET', '/api/v1/dns/reverse/' . rawurlencode($ip));
    }

    /** @return array<string, mixed> */
    public function forwardDns(string $hostname): array
    {
        return $this->request('GET', '/api/v1/dns/forward/' . rawurlencode($hostname));
    }

    /** @return array<string, mixed> */
    public function mxRecords(string $domain): array
    {
        return $this->request('GET', '/api/v1/dns/mx/' . rawurlencode($domain));
    }

    /** @return array<string, mixed> */
    public function domainAge(string $domain): array
    {
        return $this->request('GET', '/api/v1/domain/age/' . rawurlencode($domain));
    }

    /**
     * @param list<string> $domains
     * @return array<string, mixed>
     */
    public function domainAgeBatch(array $domains): array
    {
        if ($domains === []) {
            throw new \InvalidArgumentException('domains must not be empty');
        }

        return $this->request('POST', '/api/v1/domain/age/batch', ['domains' => array_values($domains)]);
    }

    // -- Account ----------------------------------------------------------------

    /** @return array<string, mixed> */
    public function rateLimit(): array
    {
        return $this->request('GET', '/api/v1/ratelimit');
    }

    /** @return array<string, mixed> */
    public function usageSummary(): array
    {
        return $this->request('GET', '/api/v1/usage/summary');
    }

    // -- Internals ------------------------------------------------------------

    /**
     * @param list<string> $items
     */
    private function checkBatch(array $items, string $name): void
    {
        if ($items === []) {
            throw new \InvalidArgumentException("{$name} must not be empty");
        }
        if (\count($items) > self::MAX_BATCH_SIZE) {
            throw new \InvalidArgumentException(
                "{$name} must contain at most " . self::MAX_BATCH_SIZE . ' items',
            );
        }
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $url = $this->baseUrl . $path;
        if ($this->apiKey !== null) {
            $url .= '?api_key=' . rawurlencode($this->apiKey);
        }

        $payload = $body !== null ? json_encode($body, \JSON_THROW_ON_ERROR) : null;
        $headers = ['User-Agent: ' . self::USER_AGENT, 'Accept: application/json'];
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        [$status, $responseHeaders, $responseBody] = $this->sendRequest($method, $url, $headers, $payload);

        if ($status >= 200 && $status < 300) {
            /** @var array<string, mixed> */
            return $responseBody === ''
                ? []
                : json_decode($responseBody, true, 512, \JSON_THROW_ON_ERROR);
        }

        throw $this->errorFor($status, $responseHeaders, $responseBody);
    }

    /**
     * Performs the HTTP exchange. Overridable for testing.
     *
     * @param list<string> $headers
     * @return array{0: int, 1: array<string, string>, 2: string} [status, lowercase headers, body]
     */
    protected function sendRequest(string $method, string $url, array $headers, ?string $payload): array
    {
        $responseHeaders = [];
        $curl = curl_init($url);
        if ($curl === false) {
            throw new IpApiError('failed to initialize curl');
        }
        curl_setopt_array($curl, [
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_TIMEOUT_MS => (int) ($this->timeout * 1000),
            \CURLOPT_HTTPHEADER => $headers,
            \CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                $parts = explode(':', $line, 2);
                if (\count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return \strlen($line);
            },
        ]);
        if ($payload !== null) {
            curl_setopt($curl, \CURLOPT_POSTFIELDS, $payload);
        }

        $responseBody = curl_exec($curl);
        if ($responseBody === false) {
            $error = curl_error($curl);
            curl_close($curl);

            throw new IpApiError("transport error: {$error}");
        }
        $status = (int) curl_getinfo($curl, \CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return [$status, $responseHeaders, (string) $responseBody];
    }

    /**
     * @param array<string, string> $headers
     */
    private function errorFor(int $status, array $headers, string $body): IpApiError
    {
        $message = '';
        $parsed = json_decode($body, true);
        if (\is_array($parsed)) {
            $message = (string) ($parsed['message'] ?? $parsed['error'] ?? '');
        } elseif ($body !== '') {
            $message = substr(trim($body), 0, 200);
        }
        if ($message === '') {
            $message = "HTTP {$status} from ip-api.io";
        }

        return match (true) {
            $status === 401 || $status === 403 => new AuthenticationError($message, $status, $body),
            $status === 429 => new RateLimitError(
                $message,
                $body,
                self::headerInt($headers, 'x-ratelimit-limit'),
                self::headerInt($headers, 'x-ratelimit-remaining'),
                self::headerInt($headers, 'x-ratelimit-reset'),
            ),
            $status === 400 || $status === 404 || $status === 422 => new InvalidRequestError($message, $status, $body),
            $status >= 500 => new ServerError($message, $status, $body),
            default => new IpApiError($message, $status, $body),
        };
    }

    /**
     * @param array<string, string> $headers
     */
    private static function headerInt(array $headers, string $name): ?int
    {
        $value = $headers[$name] ?? null;

        return $value !== null && is_numeric($value) ? (int) $value : null;
    }
}
