<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 * @license    Pimcore Open Core License (POCL)
 */


namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class ColumnConfigurationForRelationService implements ColumnConfigurationForRelationServiceInterface
{
    public function __construct(
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private ColumnConfigurationServiceInterface $columnConfigurationService
    )
    {
    }

    /**
     * @throws Exception
     */
    public function getAvailableDataObjectColumnConfigurationForRelation(
        string $classId,
        string $relationField,
        UserInterface $user
    ): array
    {
        $class = $this->classDefinitionResolver->getById($classId);

        if (!$class) {
            throw new InvalidArgumentException(sprintf('Class with ID %s not found', $classId));
        }

        $fieldDefinition = $class->getFieldDefinition($relationField);

        if (!$fieldDefinition) {
            throw new InvalidArgumentException(sprintf('Field %s not found in class %s', $relationField, $classId));
        }

        if($fieldDefinition instanceof AdvancedManyToManyObjectRelation) {
            return $this->resolveConfigurationForAdvancedManyToManyObjectRelation(
                $fieldDefinition,
                $user
            );
        }

        if($fieldDefinition instanceof ManyToManyObjectRelation) {
            return $this->resolveConfigurationForManyToManyObjectRelation(
                $fieldDefinition,
                $user
            );
        }

        throw new InvalidArgumentException(
            sprintf(
                'Field %s is not a ManyToManyObjectRelation or AdvancedManyToManyObjectRelation', $relationField
            )
        );
    }

    /**
     * @return ColumnConfiguration[]
     * @throws Exception
     */
    private function resolveConfigurationForManyToManyObjectRelation(
        ManyToManyObjectRelation $fieldDefinition,
        UserInterface $user
    ): array
    {
        $classes = $fieldDefinition->getClasses();
        $availableConfigurationsForRelation = [];
        if (count($classes) > 1) {
            $availableConfigurationsForRelation = $this->columnConfigurationService->getSystemDataObjectColumnConfiguration();
        }

        if (count($classes) === 1) {
            $classId = $this->classDefinitionResolver->getByName(
                $classes[0]['classes'],
            )->getId();

            $availableConfigurationsForRelation = $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration(
                $classId,
                0,
                $user
            );
        }

        $visibleGridFields = $this->extractVisibleGridFields($fieldDefinition);


        return $this->findConfigurations($visibleGridFields, $availableConfigurationsForRelation);
    }


    /**
     * @return ColumnConfiguration[]
     * @throws Exception
     */
    private function resolveConfigurationForAdvancedManyToManyObjectRelation(
        AdvancedManyToManyObjectRelation $fieldDefinition,
        UserInterface $user
    ): array
    {
        $classId = $this->classDefinitionResolver->getByName(
            $fieldDefinition->getAllowedClassId()
        )->getId();

        $availableConfigurationsForRelation = $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration(
            $classId,
            0,
            $user
        );

        $visibleGridFields = $this->extractVisibleGridFields($fieldDefinition);


        return $this->findConfigurations($visibleGridFields, $availableConfigurationsForRelation);
    }


    private function extractVisibleGridFields(ManyToManyObjectRelation $fieldDefinition): array
    {
        $allowedGridFields = $fieldDefinition->getVisibleFields();

        if (empty($allowedGridFields) || !is_string($allowedGridFields)) {
            return [];
        }

        return explode(',', $allowedGridFields);
    }

    /**
     * @param string[] $allowedFields
     * @param ColumnConfiguration[] $allConfigurations
     *
     * @return ColumnConfiguration[]
     */
    private function findConfigurations(array $allowedFields, array $allConfigurations): array
    {
        $configurations = [];
        foreach ($allowedFields as $allowedField) {
            foreach ($allConfigurations as $configuration) {
                if ($configuration->getKey() === $allowedField) {
                    $configurations[] = $configuration;
                    break;
                }
            }
        }

        return $configurations;
    }

}