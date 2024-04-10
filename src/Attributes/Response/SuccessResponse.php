<?php

namespace Pimcore\Bundle\StudioApiBundle\Attributes\Response;

use Attribute;
use OpenApi\Attributes\Response;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class SuccessResponse extends Response
{
    public function __construct(string $description = 'Success', mixed $content = null)
    {
        parent::__construct(
            response: 200,
            description: $description,
            content: $content
        );
    }
}