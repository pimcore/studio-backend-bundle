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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\FieldCollection\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\FieldCollection\LayoutDefinitionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldCollection\LayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\LayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinitionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class LayoutDefinitionService implements LayoutDefinitionServiceInterface
{
    public function __construct(
        private readonly DataObjectResolverInterface $dataObjectResolver,
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver,
        private readonly DefinitionResolverInterface $definitionResolver,
        private readonly LayoutDefinitionHydratorInterface $layoutDefinitionHydrator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    private array $fieldCollectionTypes = [];

    /**
     * {@inheritdoc}
     */
    public function getLayoutDefinitionsForObject(int $dataObjectId): array
    {
        $dataObject = $this->dataObjectResolver->getById($dataObjectId);

        if (!$dataObject) {
            throw new NotFoundException('data Object', $dataObjectId);
        }

        $this->collectFieldCollectionTypes(
            $this->classDefinitionResolver->getById($dataObject->getClassId())
        );

        $layoutDefinitions = [];
        foreach ($this->fieldCollectionTypes as $fieldCollectionType) {
            $layoutDefinitions[] = $this->getLayoutDefinitionByType($fieldCollectionType);
        }

        return $layoutDefinitions;
    }

    /**
     * @throws Exception
     */
    private function getLayoutDefinitionByType(string $name): LayoutDefinition
    {
        $definition = $this->definitionResolver->getByKey($name);

        if (!$definition) {
            throw new NotFoundException('Field Collection Definition', $name);
        }

        $layoutDefinition = $this->layoutDefinitionHydrator->hydrate($definition);

        $this->eventDispatcher->dispatch(
            new LayoutDefinitionEvent($layoutDefinition),
            LayoutDefinitionEvent::EVENT_NAME
        );

        return $layoutDefinition;
    }

    private function collectFieldCollectionTypes(ClassDefinitionInterface $classDefinition): void
    {
        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Fieldcollections) {
                $this->fieldCollectionTypes = [...$this->fieldCollectionTypes, ...$fieldDefinition->getAllowedTypes()];
            }
        }
    }
}
