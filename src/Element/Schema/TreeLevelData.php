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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Tree Level Data',
    required: ['level', 'elementId', 'pageNumber'],
    type: 'object'
)]
final readonly class TreeLevelData
{
    public function __construct(
        #[Property(description: 'Parent ID', type: 'int', example: 1)]
        private int $parentId,
        #[Property(description: 'Element ID', type: 'int', example: 66)]
        private int $elementId,
        #[Property(description: 'Page Number', type: 'int', example: 1)]
        private int $pageNumber,
    ) {
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function getElementId(): int
    {
        return $this->elementId;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }
}
