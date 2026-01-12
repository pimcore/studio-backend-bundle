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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection;

use Pimcore\Bundle\StudioBackendBundle\Class\Event\FieldCollection\ConfigEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldCollectionConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\FieldCollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class FieldCollectionService implements FieldCollectionServiceInterface
{
    public function __construct(
        private FieldCollectionRepositoryInterface $fieldCollectionRepository,
        private FieldCollectionConfigHydratorInterface $fieldCollectionConfigHydrator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function listFieldCollections(): Collection
    {
        $definitions = $this->fieldCollectionRepository->listFieldCollections();
        $fieldCollections = [];

        foreach ($definitions as $definition) {
            $fieldCollection = $this->fieldCollectionConfigHydrator->hydrate($definition);
            $this->eventDispatcher->dispatch(new ConfigEvent($fieldCollection), ConfigEvent::EVENT_NAME);
            $fieldCollections[] = $fieldCollection;
        }

        return new Collection(count($fieldCollections), $fieldCollections);
    }
}
