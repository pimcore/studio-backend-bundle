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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RelocatedItem;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final readonly class RelocatedItemHydrator implements RelocatedItemHydratorInterface
{
    use ElementProviderTrait;

    public function hydrate(ElementInterface $element): RelocatedItem
    {
        return new RelocatedItem(
            id: (int) $element->getId(),
            elementType: $this->getElementType($element),
            path: $element->getRealFullPath()
        );
    }
}
