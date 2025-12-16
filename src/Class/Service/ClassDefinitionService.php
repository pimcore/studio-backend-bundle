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

use Pimcore\Bundle\StudioBackendBundle\Class\Event\ClassDefinitionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ClassDefinitionFolderListEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ClassDefinitionListEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ClassDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ClassDefinitionListHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\Folder\ClassDefinitionFolderItemHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinition;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Folder;
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
        private ClassDefinitionFolderItemHydratorInterface $classDefinitionFolderListHydrator,
        private ElementServiceInterface $elementService,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getClassDefinitionCollection(): array
    {
        $hydrated = [];
        $cds = $this->classDefinitionRepository->getClassDefinitions();

        foreach ($cds as $definition) {
            $hydratedDefinition = $this->classDefinitionListHydrator->hydrate($definition);

            $this->eventDispatcher->dispatch(
                new ClassDefinitionListEvent($hydratedDefinition),
                ClassDefinitionListEvent::EVENT_NAME
            );
            $hydrated[] = $hydratedDefinition;
        }

        return $hydrated;
    }

    public function getClassDefinition(string $dataObjectClass): ClassDefinition
    {
        $cd = $this->classDefinitionHydrator->hydrate(
            $this->classDefinitionRepository->getClassDefinition($dataObjectClass)
        );
        $this->eventDispatcher->dispatch(
            new ClassDefinitionEvent($cd),
            ClassDefinitionEvent::EVENT_NAME
        );

        return $cd;
    }

    public function getClassDefinitionIdsInsideFolder(
        int $folderId
    ): array {
        $hydratedClassDefinitions = [];
        $folder = $this->elementService->getElementById(ElementTypes::TYPE_DATA_OBJECT, $folderId);
        if (!$folder instanceof Folder) {
            throw new NotFoundException(ElementTypes::TYPE_DATA_OBJECT . ' Folder', $folderId);
        }

        foreach ($folder->getDao()->getClasses() as $classDefinition) {
            $class = $this->classDefinitionFolderListHydrator->hydrate($classDefinition);
            $this->eventDispatcher->dispatch(
                new ClassDefinitionFolderListEvent($class),
                ClassDefinitionEvent::EVENT_NAME
            );
            $hydratedClassDefinitions[] = $class;
        }

        return $hydratedClassDefinitions;
    }
}
