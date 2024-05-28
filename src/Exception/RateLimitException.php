<?php

namespace Pimcore\Bundle\StudioBackendBundle\Exception;

final class RateLimitException extends AbstractApiException
{
    public function __construct()
    {
        parent::__construct(
            429,
            'Rate limit exceeded. Please try again later.'
        );
    }
}