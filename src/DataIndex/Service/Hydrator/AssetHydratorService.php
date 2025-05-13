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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResultItem;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Archive;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Audio;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Document;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Text;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Unknown;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\AssetHydratorInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;
use function get_class;

/**
 * @internal
 */
final readonly class AssetHydratorService implements AssetHydratorServiceInterface
{
    public function __construct(
        private AssetHydratorInterface $assetHydrator,
        private ServiceProviderInterface $hydratorLocator,
    ) {
    }

    public function hydrateAssets(
        AssetSearchResultItem $item
    ): Asset|Archive|Audio|Document|AssetFolder|Image|Text|Unknown|Video {
        $class = get_class($item);
        if ($this->hydratorLocator->has($class)) {
            return $this->hydratorLocator->get($class)->hydrate($item);
        }

        return $this->assetHydrator->hydrate($item);
    }
}
