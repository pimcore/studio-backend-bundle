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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'ElementPath',
    title: 'Element Path',
    required: ['elementPath'],
    type: 'object'
)]
final readonly class ElementPath
{
    public function __construct(
        #[Property(description: 'Element Path', type: 'string', example: 'path/to/element')]
        private string $elementPath
    ) {
    }

    public function getElementPath(): string
    {
        return $this->elementPath;
    }
}
