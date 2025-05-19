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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

/**
 * @internal
 */
final readonly class Filters
{
    public function __construct(
        private array $filters = [],
        private array $assetFilters = [],
        private array $dataObjectFilters = [],
        private array $documentFilters = []
    ) {
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getAssetFilters(): array
    {
        return $this->assetFilters;
    }

    public function getDataObjectFilters(): array
    {
        return $this->dataObjectFilters;
    }

    public function getDocumentFilters(): array
    {
        return $this->documentFilters;
    }
}
