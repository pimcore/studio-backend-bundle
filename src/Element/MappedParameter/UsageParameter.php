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

namespace Pimcore\Bundle\StudioBackendBundle\Element\MappedParameter;

use JsonException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;

/**
 * @internal
 */
final readonly class UsageParameter
{
    /**
     * @throws JsonException
     */
    public function __construct(
        private int $page = 1,
        private int $pageSize = 50,
        private ?string $sortBy = null,
        private ?string $sortOrder = null
    ) {

    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }
}
