<?php

declare(strict_types=1);

namespace IpApiIo\Tests;

use IpApiIo\AuthenticationError;
use IpApiIo\Client;
use IpApiIo\InvalidRequestError;
use IpApiIo\RateLimitError;
use IpApiIo\ServerError;
use PHPUnit\Framework\TestCase;

/**
 * Client with the HTTP exchange stubbed out for offline testing.
 */
final class FakeClient extends Client
{
    /** @var list<array{method: string, url: string, headers: list<string>, payload: ?string}> */
    public array $requests = [];

    public int $responseStatus = 200;

    /** @var array<string, string> */
    public array $responseHeaders = [];

    public string $responseBody = '{}';

    protected function sendRequest(string $method, string $url, array $headers, ?string $payload): array
    {
        $this->requests[] = [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'payload' => $payload,
        ];

        return [$this->responseStatus, $this->responseHeaders, $this->responseBody];
    }
}

final class ClientTest extends TestCase
{
    // IpInfoV1Dto example from https://ip-api.io/openapi.json
    private const IP_INFO_FIXTURE = [
        'ip' => '203.0.113.195',
        'isp' => 'Comcast Cable Communications',
        'asn' => 'AS7922',
        'suspicious_factors' => [
            'is_proxy' => false, 'is_tor_node' => false, 'is_spam' => false,
            'is_crawler' => false, 'is_datacenter' => true, 'is_vpn' => false, 'is_threat' => false,
        ],
        'location' => [
            'country' => 'United States', 'country_code' => 'US', 'city' => 'San Francisco',
            'latitude' => 37.7749, 'longitude' => -122.4194, 'zip' => '94105',
            'timezone' => 'America/Los_Angeles', 'local_time' => '2023-06-21T14:30:00-07:00',
            'local_time_unix' => 1687385400, 'is_daylight_savings' => true,
        ],
    ];

    public function testLookupParsesResponseAndSendsUserAgent(): void
    {
        $client = new FakeClient();
        $client->responseBody = json_encode(self::IP_INFO_FIXTURE, \JSON_THROW_ON_ERROR);

        $info = $client->lookup('203.0.113.195');

        self::assertSame(self::IP_INFO_FIXTURE, $info);
        $request = $client->requests[0];
        self::assertSame('GET', $request['method']);
        self::assertSame('https://ip-api.io/api/v1/ip/203.0.113.195', $request['url']);
        self::assertContains('User-Agent: ipapi-io-php/' . Client::VERSION, $request['headers']);
    }

    public function testApiKeySentAsQueryParam(): void
    {
        $client = new FakeClient(apiKey: 'secret123');
        $client->lookup();

        self::assertSame('https://ip-api.io/api/v1/ip?api_key=secret123', $client->requests[0]['url']);
    }

    public function testEmailPathIsUrlEncoded(): void
    {
        $client = new FakeClient();
        $client->validateEmail('user+tag@example.com');

        self::assertSame(
            'https://ip-api.io/api/v1/email/advanced/user%2Btag%40example.com',
            $client->requests[0]['url'],
        );
    }

    public function testBatchPostSendsJsonBody(): void
    {
        $client = new FakeClient();
        $client->responseBody = '{"results": {}}';
        $client->lookupBatch(['8.8.8.8', '1.1.1.1']);

        $request = $client->requests[0];
        self::assertSame('POST', $request['method']);
        self::assertSame('https://ip-api.io/api/v1/ip/batch', $request['url']);
        self::assertSame('{"ips":["8.8.8.8","1.1.1.1"]}', $request['payload']);
        self::assertContains('Content-Type: application/json', $request['headers']);
    }

    public function testBatchSizeValidation(): void
    {
        $client = new FakeClient();

        $this->expectException(\InvalidArgumentException::class);
        $client->lookupBatch([]);
    }

    public function testOversizedBatchRejected(): void
    {
        $client = new FakeClient();

        $this->expectException(\InvalidArgumentException::class);
        $client->lookupBatch(array_fill(0, 101, '1.1.1.1'));
    }

    public function testRateLimitErrorExposesHeaders(): void
    {
        $client = new FakeClient();
        $client->responseStatus = 429;
        $client->responseBody = '{"message": "Rate limit exceeded"}';
        $client->responseHeaders = [
            'x-ratelimit-limit' => '1000',
            'x-ratelimit-remaining' => '0',
            'x-ratelimit-reset' => '1718200000',
        ];

        try {
            $client->lookup('8.8.8.8');
            self::fail('expected RateLimitError');
        } catch (RateLimitError $error) {
            self::assertSame(429, $error->statusCode);
            self::assertSame(1000, $error->limit);
            self::assertSame(0, $error->remaining);
            self::assertSame(1718200000, $error->reset);
            self::assertStringContainsString('Rate limit exceeded', $error->getMessage());
        }
    }

    public function testAuthenticationErrorOn401(): void
    {
        $client = new FakeClient(apiKey: 'bad');
        $client->responseStatus = 401;
        $client->responseBody = '{"error": "Invalid API key"}';

        $this->expectException(AuthenticationError::class);
        $client->lookup();
    }

    public function testInvalidRequestErrorOn400(): void
    {
        $client = new FakeClient();
        $client->responseStatus = 400;
        $client->responseBody = '{"message": "Invalid IP address"}';

        $this->expectException(InvalidRequestError::class);
        $client->lookup('not-an-ip');
    }

    public function testServerErrorOn500(): void
    {
        $client = new FakeClient();
        $client->responseStatus = 500;

        $this->expectException(ServerError::class);
        $client->lookup();
    }
}
