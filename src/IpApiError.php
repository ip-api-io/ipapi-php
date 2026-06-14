<?php

declare(strict_types=1);

namespace IpApiIo;

/**
 * Base error for all ip-api.io client failures.
 */
class IpApiError extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?string $body = null,
    ) {
        parent::__construct($message);
    }
}
