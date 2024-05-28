<?php

namespace Pimcore\Bundle\StudioBackendBundle\Exception;

final class SendMailException extends AbstractApiException
{
    public function __construct()
    {
        parent::__construct(
            500,
            'Failed to send reset password mail'
        );
    }
}