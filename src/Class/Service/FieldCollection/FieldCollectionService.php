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
use Pimcore\Bundle\StudioBackendBundle\Class\Event\FieldCollection\DetailEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\FieldCollection\UsageDataEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldCollection\DetailHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldCollectionConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateFieldCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\FieldCollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\FieldCollectionDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollectionUsageData;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\JsonExport;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
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
        private DetailHydratorInterface $detailHydrator,
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

    /**
     * {@inheritdoc}
     */
    public function getFieldCollectionByKey(string $key): FieldCollectionDetail
    {
        return $this->hydrateDetail(
            $this->fieldCollectionRepository->getFieldCollectionByKey($key)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createFieldCollection(CreateFieldCollectionParameters $parameters): FieldCollectionDetail
    {
        return $this->hydrateDetail(
            $this->fieldCollectionRepository->create($parameters)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updateFieldCollection(string $key, UpdateParameters $parameters): FieldCollectionDetail
    {
        $definition = $this->fieldCollectionRepository->getFieldCollectionByKey($key);

        return $this->hydrateDetail(
            $this->fieldCollectionRepository->update($definition, $parameters)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFieldCollection(string $key): void
    {
        $definition = $this->fieldCollectionRepository->getFieldCollectionByKey($key);
        $this->fieldCollectionRepository->delete($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function exportFieldCollection(string $key): JsonExport
    {
        $definition = $this->fieldCollectionRepository->getFieldCollectionByKey($key);
        $json = $this->fieldCollectionRepository->exportAsJson($definition);

        return new JsonExport(
            $json,
            'fieldcollection_' . $definition->getKey() . '_export.json'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function importFieldCollectionFromJson(string $key, string $json): FieldCollectionDetail
    {
        $definition = $this->fieldCollectionRepository->getFieldCollectionByKey($key);
        $definition = $this->fieldCollectionRepository->importFromJson($definition, $json);

        return $this->hydrateDetail($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldCollectionUsages(string $key): array
    {
        $this->fieldCollectionRepository->getFieldCollectionByKey($key);
        $usages = $this->fieldCollectionRepository->getFieldCollectionUsages($key);
        $hydratedUsages = [];

        foreach ($usages as $usage) {
            $usageData = new FieldCollectionUsageData($usage['class'], $usage['field']);
            $this->eventDispatcher->dispatch(new UsageDataEvent($usageData), UsageDataEvent::EVENT_NAME);
            $hydratedUsages[] = $usageData;
        }

        return $hydratedUsages;
    }

    private function hydrateDetail(Definition $definition): FieldCollectionDetail
    {
        $detail = $this->detailHydrator->hydrate($definition);
        $this->eventDispatcher->dispatch(new DetailEvent($detail), DetailEvent::EVENT_NAME);

        return $detail;
    }
}
