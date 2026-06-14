<?php

// Live smoke test against https://ip-api.io.
// Usage: IPAPI_API_KEY=... php examples/smoke.php
// The API requires a key; without IPAPI_API_KEY this script skips.

declare(strict_types=1);

require __DIR__ . '/../src/IpApiError.php';
require __DIR__ . '/../src/AuthenticationError.php';
require __DIR__ . '/../src/RateLimitError.php';
require __DIR__ . '/../src/InvalidRequestError.php';
require __DIR__ . '/../src/ServerError.php';
require __DIR__ . '/../src/Client.php';

$apiKey = getenv('IPAPI_API_KEY');
if ($apiKey === false || $apiKey === '') {
    echo "SKIPPED: set IPAPI_API_KEY to run the live smoke test\n";
    exit(0);
}

$client = new \IpApiIo\Client(apiKey: $apiKey);

$info = $client->lookup('8.8.8.8');
if ($info['ip'] !== '8.8.8.8') {
    throw new \RuntimeException('unexpected response: ' . json_encode($info));
}
echo "lookup(8.8.8.8): {$info['location']['country']} / {$info['asn']}\n";

$rateLimit = $client->rateLimit();
echo "rate_limit: plan={$rateLimit['plan_id']} ip_api remaining={$rateLimit['ip_api']['remaining']}\n";

echo "smoke OK\n";
