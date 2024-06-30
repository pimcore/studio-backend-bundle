<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Create Tag Parameters',
    description: 'Parameters for creating a new tag',
    type: 'object'
)]
final readonly class CreateTagParameters
{
    public function __construct(
        #[Property(description: 'Parent id', type: 'int', example: 0)]
        private int $parentId,
        #[Property(description: 'Tag name', type: 'string', example: 'tag 1')]
        private string $name,
    ) {
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
