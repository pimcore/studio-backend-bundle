<?php

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'Unauthorized',
    description: 'Bad credentials or missing token',
    type: 'object'
)]
final readonly class Unauthorized
{
    #[Property(description: 'Message', type: 'string')]
    public string $message;
}