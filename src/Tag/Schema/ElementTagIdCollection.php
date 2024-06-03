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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Collection of Element and Tag ids',
    type: 'object'
)]
final readonly class ElementTagIdCollection
{
    public function __construct(
        #[Property(
            description: 'element ids',
            type: 'array',
            items: new Items(type: 'integer', example: 1),
            example: [1,2,3]
        )]
        private array $elementIds,
        #[Property(
            description: 'tag ids',
            type: 'array',
            items: new Items(type: 'integer', example: 1),
            example: [1,2,3]
        )]
        private array $tagIds,
    )
    {
    }

    public function getElementIds(): array
    {
        return $this->elementIds;
    }

    public function getTagsIds(): array
    {
        return $this->tagIds;
    }

}
