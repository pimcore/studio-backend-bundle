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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\SimpleSearchResult;

/**
 * @internal
 */
final readonly class SimpleSearchHydrator implements SimpleSearchHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService
    ) {
    }

    public function hydrate(ElementSearchResultItemInterface $resultItem): SimpleSearchResult
    {
        return new SimpleSearchResult(
            $resultItem->getId(),
            $resultItem->getElementType()->value,
            $resultItem->getType(),
            $resultItem->getFullPath(),
            $this->iconService->getIconForElement($resultItem)
        );
    }
}
