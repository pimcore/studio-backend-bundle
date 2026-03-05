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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration;

/**
 * @internal
 */
final readonly class GetPageParameters
{
    public function __construct(
        private string $table = 'keys',
        private int $id = 0,
        private int $storeId = 0,
        private int $pageSize = 15,
        private string $sortKey = 'name',
        private string $sortDir = 'ASC',
    ) {
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getSortKey(): string
    {
        return $this->sortKey;
    }

    public function getSortDir(): string
    {
        return $this->sortDir;
    }
}
