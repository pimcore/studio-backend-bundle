<?php

namespace Pimcore\Bundle\StudioApiBundle\Config\OpenApi;

use OpenApi\Attributes\SecurityScheme;

#[SecurityScheme(
    securityScheme: 'auth_token',
    type: 'http',
    description: 'Bearer token for authentication',
    name: 'auth_token',
    scheme: 'bearer'
)]
final class Security
{

}