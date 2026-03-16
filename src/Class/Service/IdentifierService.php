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

use Doctrine\DBAL\Exception;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\CustomLayout\CustomLayoutResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ClassDefinitionIdentifierDataEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\CustomLayoutIdentifierDataEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ClassDefinitionIdHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\CustomLayout\CustomLayoutIdHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionIdentifierData;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutIdentifierData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\UuidV4;

/**
 * @internal
 */
final readonly class IdentifierService implements IdentifierServiceInterface
{
    public function __construct(
        private DbResolverInterface $dbResolver,
        private ClassDefinitionIdHydratorInterface $classDefinitionIdHydrator,
        private CustomLayoutIdHydratorInterface $customLayoutIdentifierDataHydrator,
        private CustomLayoutResolverInterface $customLayoutResolver,
        private CustomLayoutRepositoryInterface $customLayoutRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getClassIdentifierData(): ClassDefinitionIdentifierData
    {
        $data = $this->classDefinitionIdHydrator->hydrate(
            $this->getSuggestedClassIdentifier(),
            $this->getExistingClassIdentifiers()
        );

        $this->eventDispatcher->dispatch(
            new ClassDefinitionIdentifierDataEvent($data),
            ClassDefinitionIdentifierDataEvent::EVENT_NAME
        );

        return $data;
    }

    public function getCustomLayoutIdentifierData(string $classDefinitionId): CustomLayoutIdentifierData
    {
        $data = $this->customLayoutIdentifierDataHydrator->hydrate(
            $this->getSuggestedCustomLayoutIdentifier($classDefinitionId)->toRfc4122(),
            $this->getExistingCustomLayoutIdentifiers(),
            $this->getExistingCustomLayoutNames($classDefinitionId)
        );

        $this->eventDispatcher->dispatch(
            new CustomLayoutIdentifierDataEvent($data),
            CustomLayoutIdentifierDataEvent::EVENT_NAME
        );

        return $data;
    }

    /**
     * @throws DatabaseException
     */
    private function getSuggestedClassIdentifier(): string
    {
        try {
            $maxId = $this->dbResolver->get()->fetchOne('SELECT MAX(CAST(id AS SIGNED)) FROM classes;');
            $suggestedId = $maxId ? ((int)$maxId + 1) : 1;

            return (string)$suggestedId;
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * @throws DatabaseException
     */
    private function getExistingClassIdentifiers(): array
    {
        try {
            return $this->dbResolver->get()->fetchFirstColumn('select LOWER(id) from classes');
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    private function getSuggestedCustomLayoutIdentifier(string $classDefinitionId): UuidV4
    {
        $uid = $this->customLayoutResolver->getIdentifier($classDefinitionId);

        return $uid ?? UuidV4::v4();
    }

    private function getExistingCustomLayoutNames(string $classDefinitionId): array
    {
        $existingNames = [];
        $allLayouts = $this->customLayoutRepository->getCustomLayoutsByClass([$classDefinitionId]);
        foreach ($allLayouts as $layout) {
            $existingNames[] = $layout->getName();
        }

        return $existingNames;
    }

    private function getExistingCustomLayoutIdentifiers(): array
    {
        $existingIds = [];
        $allLayouts = $this->customLayoutRepository->getAllCustomLayouts();
        foreach ($allLayouts as $layout) {
            $existingIds[] = $layout->getId();
        }

        return $existingIds;
    }
}
