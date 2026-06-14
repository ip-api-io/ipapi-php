<?php

declare(strict_types=1);

namespace IpApiIo;

/**
 * HTTP 429 — quota exhausted.
 *
 * Exposes the x-ratelimit-* response headers; $reset is the unix timestamp
 * when the quota renews. The client never retries.
 */
class RateLimitError extends IpApiError
{
    public function __construct(
        string $message,
        ?string $body = null,
        public readonly ?int $limit = null,
        public readonly ?int $remaining = null,
        public readonly ?int $reset = null,
    ) {
        parent::__construct($message, 429, $body);
    }
}
