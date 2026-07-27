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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException as ApiParseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\DotNotationParserInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\UserInterface;
use function count;
use function in_array;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final readonly class ColumnConfigurationForRelationService implements ColumnConfigurationForRelationServiceInterface
{
    public function __construct(
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private ColumnConfigurationServiceInterface $columnConfigurationService,
        private DotNotationParserInterface $dotNotationParser
    ) {
    }

    /**
     * @throws Exception
     */
    public function getAvailableDataObjectColumnConfigurationForRelation(
        string $classId,
        string $relationField,
        UserInterface $user
    ): array {
        $class = $this->classDefinitionResolver->getById($classId);

        if (!$class) {
            throw new InvalidArgumentException(sprintf('Class with ID %s not found', $classId));
        }

        try {
            $fieldDefinition = $this->dotNotationParser->parseByClassId($classId, $relationField)->getFieldDefinition();
        } catch (ParseException $exception) {
            throw new ApiParseException($exception->getMessage());
        }

        if ($fieldDefinition instanceof AdvancedManyToManyObjectRelation) {
            return $this->resolveConfigurationForAdvancedManyToManyObjectRelation(
                $fieldDefinition,
                $user
            );
        }

        if ($fieldDefinition instanceof ManyToManyObjectRelation) {
            return $this->resolveConfigurationForManyToManyObjectRelation(
                $fieldDefinition,
                $user
            );
        }

        throw new InvalidArgumentException(
            sprintf(
                'Field %s is not a ManyToManyObjectRelation or AdvancedManyToManyObjectRelation',
                $relationField
            )
        );
    }

    /**
     * @return ColumnConfiguration[]
     *
     * @throws Exception
     */
    private function resolveConfigurationForManyToManyObjectRelation(
        ManyToManyObjectRelation $fieldDefinition,
        UserInterface $user
    ): array {
        $classes = $fieldDefinition->getClasses();
        $availableConfigurationsForRelation = [];

        foreach ($classes as $class) {
            if ($class['classes'] === ElementTypes::TYPE_FOLDER) {
                continue;
            }

            $classDefinition = $this->classDefinitionResolver->getByName(
                $class['classes'],
            );

            if (!$classDefinition) {
                continue;
            }

            $classId = $classDefinition->getId();

            $availableConfigurationsForRelation[$classId] = $this->columnConfigurationService
                ->getAvailableDataObjectColumnConfiguration(
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
     *
     * @throws Exception
     */
    private function resolveConfigurationForAdvancedManyToManyObjectRelation(
        AdvancedManyToManyObjectRelation $fieldDefinition,
        UserInterface $user
    ): array {
        $allowedClassId = $fieldDefinition->getAllowedClassId();

        if ($allowedClassId === ElementTypes::TYPE_FOLDER) {
            return [];
        }

        $classDefinition = $this->classDefinitionResolver->getByName($allowedClassId);

        if (!$classDefinition) {
            return [];
        }

        $classId = $classDefinition->getId();

        $availableConfigurationsForRelation[$classId] = $this->columnConfigurationService
            ->getAvailableDataObjectColumnConfiguration(
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
     * @param array<string, ColumnConfiguration[]> $allConfigurations
     *
     * @return ColumnConfiguration[]
     */
    private function findConfigurations(array $allowedFields, array $allConfigurations): array
    {
        $groupedByKey = [];

        foreach ($allConfigurations as $configurations) {
            foreach ($configurations as $config) {
                if (in_array($config->getKey(), $allowedFields, true)) {
                    $groupedByKey[$config->getKey()][] = $config;
                }
            }
        }

        return $this->pickUniqueConfigPerKeyIfSameFrontendType($groupedByKey);
    }

    /**
     * @param array<string, ColumnConfiguration[]> $configurations
     *
     * @return ColumnConfiguration[]
     */
    public function pickUniqueConfigPerKeyIfSameFrontendType(array $configurations): array
    {
        $result = [];

        foreach ($configurations as $key => $configs) {
            if ($configs === []) {
                continue;
            }

            $frontendTypes = array_map(
                static fn ($c) => $c->getFrontendType(),
                $configs
            );

            $unique = array_values(array_unique($frontendTypes));

            if (count($unique) === 1) {
                $result[$key] = $configs[0];
            }
        }

        return $result;
    }
}
