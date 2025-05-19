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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\DataObjectHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Symfony\Contracts\Service\ServiceProviderInterface;
use function get_class;

/**
 * @internal
 */
final readonly class DataObjectHydratorService implements DataObjectHydratorServiceInterface
{
    public function __construct(
        private DataObjectHydratorInterface $dataObjectHydrator,
        private ServiceProviderInterface $hydratorLocator,
    ) {
    }

    public function hydrateDataObjects(DataObjectSearchResultItem $item): DataObject|DataObjectFolder
    {
        $class = get_class($item);
        if ($this->hydratorLocator->has($class)) {
            return $this->hydratorLocator->get($class)->hydrate($item);
        }

        return $this->dataObjectHydrator->hydrate($item);
    }
}
