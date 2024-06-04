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


use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;

/**
 * @internal
 */
final readonly class TagsParameters extends CollectionParameters
{
    public function __construct(
        int $page = 1,
        int $pageSize = 50,
        private ?int $parentId = null,
        private ?string $filter = null,
    ) {
        parent::__construct($page, $pageSize);
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getFilter(): ?string
    {
        return $this->filter;
    }
}
