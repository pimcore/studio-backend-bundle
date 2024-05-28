<?php

namespace Pimcore\Bundle\StudioBackendBundle\Exception;

final class DomainConfigurationException extends AbstractApiException
{
    public function __construct()
    {
        parent::__construct(
            500,
            'No main domain set in system settings, unable to generate reset password link'
        );
    }
}