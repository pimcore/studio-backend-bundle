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

namespace Pimcore\Bundle\StudioApiBundle\Service\CoreData\V1\Hydrator;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Model\Asset as CoreAsset;
use Symfony\Contracts\Service\ServiceProviderInterface;

final readonly class AssetHydratorService implements AssetHydratorServiceInterface
{
    public function __construct(
        private ServiceProviderInterface $assetHydratorLocator,
    ) {
    }

    public function hydrate(CoreAsset $item): ?Asset
    {
        $class = get_class($item);
        if($this->assetHydratorLocator->has($class)) {
            return $this->assetHydratorLocator->get($class)->hydrate($item);
        }

        return null;
        //return new Asset($item->getId());
    }
}
