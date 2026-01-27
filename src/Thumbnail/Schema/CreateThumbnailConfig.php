<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'CreateThumbnailConfig',
    title: 'Create Thumbnail Config',
    required: ['name'],
    type: 'object'
)]
final readonly class CreateThumbnailConfig
{
    public function __construct(
        #[Property(description: 'Thumbnail configuration name', type: 'string', example: 'my-thumbnail')]
        private string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
