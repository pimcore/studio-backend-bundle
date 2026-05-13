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

use Override;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ClassDefinitionBrickEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ClassDefinitionBrickFieldEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ClassDefinitionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ClassDefinitionListEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ClassDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ClassDefinitionListHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateClassDefinitionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\JsonExport;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\DataObject\ClassDefinition as CoreClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class ClassDefinitionService implements ClassDefinitionServiceInterface
{
    public function __construct(
        private ClassDefinitionRepositoryInterface $classDefinitionRepository,
        private ClassDefinitionHydratorInterface $classDefinitionHydrator,
        private ClassDefinitionListHydratorInterface $classDefinitionListHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function createClassDefinition(CreateClassDefinitionParameters $parameters): ClassDefinition
    {
        return $this->hydrateClassDefinition(
            $this->classDefinitionRepository->create($parameters)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updateClassDefinition(string $id, UpdateParameters $updateParameters): ClassDefinition
    {
        return $this->hydrateClassDefinition(
            $this->classDefinitionRepository->update(
                $this->classDefinitionRepository->getClassDefinitionById($id),
                $updateParameters
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteClassDefinition(string $id): void
    {
        $this->classDefinitionRepository->delete(
            $this->classDefinitionRepository->getClassDefinitionById($id)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function exportClassDefinition(string $id): JsonExport
    {
        $classDefinition = $this->classDefinitionRepository->getClassDefinitionById($id);
        $json = $this->classDefinitionRepository->exportAsJson($classDefinition);

        return new JsonExport(
            $json,
            'class_' . $classDefinition->getName() . '_export.json'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function importClassDefinitionFromJson(string $id, string $json): ClassDefinition
    {
        $classDefinition = $this->classDefinitionRepository->getClassDefinitionById($id);
        $classDefinition = $this->classDefinitionRepository->importFromJson($classDefinition, $json);

        return $this->hydrateClassDefinition($classDefinition);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassDefinitionCollection(
        bool $creatableOnly = false
    ): array {
        return $this->hydrateClassDefinitionList($this->getClassDefinitions($creatableOnly));
    }

    /**
     * {@inheritdoc}
     */
    public function getClassDefinitionsWithObjectBricks(): array
    {
        return $this->hydrateClassDefinitionList(
            $this->classDefinitionRepository->getClassDefinitionsWithObjectBricks()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getClassDefinitionByName(string $dataObjectClass): ClassDefinition
    {

        return $this->hydrateClassDefinition($this->classDefinitionRepository->getClassDefinition($dataObjectClass));
    }

    /**
     * {@inheritdoc}
     */
    public function getClassDefinitionById(string $id): ClassDefinition
    {

        return $this->hydrateClassDefinition($this->classDefinitionRepository->getClassDefinitionById($id));
    }

    /**
     * {@inheritdoc}
     */
    public function getClassDefinitionBricks(string $id): array
    {
        $class = $this->classDefinitionRepository->getClassDefinitionById($id);
        $bricks = $this->classDefinitionRepository->getObjectBricksByClassName($class->getName());
        $hydratedBricks = [];

        foreach ($bricks as $brick) {
            $brickData = $this->classDefinitionHydrator->hydrateBrickData($brick['key'], $brick['fieldname']);
            $this->eventDispatcher->dispatch(
                new ClassDefinitionBrickEvent($brickData),
                ClassDefinitionBrickEvent::EVENT_NAME
            );
            $hydratedBricks[] = $brickData;
        }

        return $hydratedBricks;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassDefinitionBrickFields(string $id): array
    {
        $class = $this->classDefinitionRepository->getClassDefinitionById($id);

        $brickFields = [];
        foreach ($class->getFieldDefinitions() as $fieldName => $fieldDefinition) {
            if (!$fieldDefinition instanceof Objectbricks) {
                continue;
            }

            $brickField = $this->classDefinitionHydrator->hydrateBrickField($fieldName);
            $this->eventDispatcher->dispatch(
                new ClassDefinitionBrickFieldEvent($brickField),
                ClassDefinitionBrickFieldEvent::EVENT_NAME
            );
            $brickFields[] = $brickField;
        }

        return $brickFields;
    }

    /**
     * @param CoreClassDefinition[] $classDefinitions
     */
    private function hydrateClassDefinitionList(array $classDefinitions): array
    {
        $hydrated = [];

        foreach ($classDefinitions as $definition) {
            $hydratedDefinition = $this->classDefinitionListHydrator->hydrate($definition);

            $this->eventDispatcher->dispatch(
                new ClassDefinitionListEvent($hydratedDefinition),
                ClassDefinitionListEvent::EVENT_NAME
            );
            $hydrated[] = $hydratedDefinition;
        }

        return $hydrated;
    }

    private function getClassDefinitions(bool $creatableOnly = false): array
    {
        $cds = $this->classDefinitionRepository->getClassDefinitions();
        if (!$creatableOnly) {
            return $cds;
        }

        $currentUser = $this->securityService->getCurrentUser();
        $allowedDefinitions = [];
        foreach ($cds as $definition) {
            if (
                !$currentUser->isAllowed(
                    $definition->getId(),
                    UserPermissions::CLASS_DEFINITION->value
                )
            ) {
                continue;
            }

            $allowedDefinitions[] = $definition;
        }

        return $allowedDefinitions;
    }

    private function hydrateClassDefinition(CoreClassDefinition $classDefinition): ClassDefinition
    {
        $cd = $this->classDefinitionHydrator->hydrate($classDefinition);
        $this->eventDispatcher->dispatch(
            new ClassDefinitionEvent($cd),
            ClassDefinitionEvent::EVENT_NAME
        );

        return $cd;
    }
}
