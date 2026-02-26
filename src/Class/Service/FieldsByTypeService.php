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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\Event\FieldByTypeEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldByTypeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\FieldsByTypeParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldByType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class FieldsByTypeService implements FieldsByTypeServiceInterface
{
    private const int ROOT_FOLDER_ID = 1;

    private const string OBJECT_BRICK_TYPE = 'objectbricks';

    private const string OBJECT_BRICK_COLUMN_TYPE = 'dataobject.objectbrick';

    public function __construct(
        private ColumnConfigurationServiceInterface $columnConfigurationService,
        private SecurityServiceInterface $securityService,
        private FieldByTypeHydratorInterface $hydrator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getFieldsByType(FieldsByTypeParameters $parameters): array
    {
        $allColumns = $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration(
            $parameters->getClassId(),
            self::ROOT_FOLDER_ID,
            $this->securityService->getCurrentUser()
        );

        $types = $parameters->getTypes();
        $filtered = $this->filterByTypes($allColumns, $types);

        return $this->deduplicateAndHydrate($filtered);
    }

    /**
     * @param ColumnConfiguration[] $columns
     *
     * @return FieldByType[]
     */
    private function deduplicateAndHydrate(array $columns): array
    {
        $results = [];
        $seenKeys = [];
        foreach ($columns as $col) {
            $key = $this->hydrator->resolveFieldKey($col);
            if (isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;
            $results[] = $this->hydrateField($key);
        }

        return $results;
    }

    private function hydrateField(string $key): FieldByType
    {
        $fieldByType = $this->hydrator->hydrate($key);
        $this->eventDispatcher->dispatch(
            new FieldByTypeEvent($fieldByType),
            FieldByTypeEvent::EVENT_NAME,
        );

        return $fieldByType;
    }

    /**
     * @param ColumnConfiguration[] $columns
     * @param string[] $types
     *
     * @return ColumnConfiguration[]
     */
    private function filterByTypes(array $columns, array $types): array
    {
        return array_filter(
            $columns,
            function (ColumnConfiguration $col) use ($types): bool {
                foreach ($types as $type) {
                    if ($type === self::OBJECT_BRICK_TYPE && $this->isObjectBrickColumn($col)) {
                        return true;
                    }

                    if ($col->getFrontendType() === $type) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    private function isObjectBrickColumn(ColumnConfiguration $col): bool
    {
        return str_contains($col->getType(), self::OBJECT_BRICK_COLUMN_TYPE);
    }
}
