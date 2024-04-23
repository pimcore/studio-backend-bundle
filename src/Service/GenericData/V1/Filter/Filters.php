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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Filter;

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
