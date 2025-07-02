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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'WebsiteSettingsObjectData',
    title: 'Website Settings Object Data',
    required: ['id', 'path'],
    type: 'object'
)]
final readonly class ElementParameter
{
    public function __construct(
        #[Property(description: 'id', type: 'int', example: 1020)]
        private int $id,
        #[Property(description: 'path', type: 'string', example: '/path/to/object')]
        private string $path,
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
