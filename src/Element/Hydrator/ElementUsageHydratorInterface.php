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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ElementUsage;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ElementUsageItem;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
interface ElementUsageHydratorInterface
{
    /**
     * @param array<ElementUsageItem> $usageItems
     */
    public function hydrateUsage(
        array $usageItems,
        bool $hasHidden,
        int $totalCount
    ): ElementUsage;

    public function hydrateUsageItem(ElementInterface $element): ElementUsageItem;
}
