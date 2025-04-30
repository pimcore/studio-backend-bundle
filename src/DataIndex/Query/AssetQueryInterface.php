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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Query;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearchInterface;

/**
 * @internal
 */
interface AssetQueryInterface extends QueryInterface
{
    public function filterMetadata(string $name, string $type, mixed $data): self;

    public function filterDatetime(
        string $field,
        int|null $startDate = null,
        int|null $endDate = null,
        int|null $onDate = null,
        bool $roundToDay = true,
        bool $enablePqlFieldNameResolution = true
    ): self;

    public function getSearch(): AssetSearchInterface;
}
