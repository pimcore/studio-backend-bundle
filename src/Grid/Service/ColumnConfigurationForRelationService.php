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
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\UserInterface;
use function array_key_exists;
use function count;
use function in_array;
use function is_string;
use function sprintf;
use function strlen;

/**
 * @internal
 */
final readonly class ColumnConfigurationForRelationService implements ColumnConfigurationForRelationServiceInterface
{
    private const string CLASS_SOURCE_PREFIX = 'object:';

    private const string DATA_OBJECT_ADAPTER_TYPE = 'dataobject.adapter';

    private const string PREDEFINED_METADATA_GROUP = 'predefined_metadata';

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

        // also covers AdvancedManyToManyRelation, which extends ManyToManyRelation
        if ($fieldDefinition instanceof ManyToManyRelation) {
            return $this->resolveConfigurationForManyToManyRelation(
                $fieldDefinition,
                $user
            );
        }

        throw new InvalidArgumentException(
            sprintf(
                'Field %s is not a ManyToManyObjectRelation, AdvancedManyToManyObjectRelation, ' .
                'ManyToManyRelation or AdvancedManyToManyRelation',
                $relationField
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibleFieldClassColumnConfiguration(string $className, UserInterface $user): array
    {
        $classDefinition = $this->classDefinitionResolver->getByName($className);

        if (!$classDefinition) {
            return [];
        }

        $columns = [];
        $configurations = $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration(
            $classDefinition->getId(),
            0,
            $user
        );

        foreach ($configurations as $configuration) {
            if ($configuration->getType() === self::DATA_OBJECT_ADAPTER_TYPE
                && !array_key_exists($configuration->getKey(), $columns)
            ) {
                $columns[$configuration->getKey()] = $configuration;
            }
        }

        return $columns;
    }

    /**
     * The visible fields of the generic relation types come from several element types: the fields of the
     * allowed classes, the properties every element has and the fields of assets. The relation decides which
     * names it offers and where each one comes from ({@see ManyToManyRelation::getVisibleFieldSources()});
     * the first source a column configuration exists for describes the column, the same "first definition
     * wins" order the relation uses to describe the field.
     *
     * A class field is described by the user's column configuration of that class, so a field the user may
     * not see there does not become a column through the relation either - as for the object relation
     * types. The properties and asset fields are described by the corresponding asset grid columns.
     *
     * @return array<string, ColumnConfiguration>
     *
     * @throws Exception
     */
    private function resolveConfigurationForManyToManyRelation(
        ManyToManyRelation $fieldDefinition,
        UserInterface $user
    ): array {
        $visibleFieldNames = $fieldDefinition->getVisibleFieldNames();

        if ($visibleFieldNames === []) {
            return [];
        }

        $sources = $fieldDefinition->getVisibleFieldSources();
        $classColumns = [];
        $elementColumns = null;
        $result = [];

        foreach ($visibleFieldNames as $name) {
            foreach ($sources[$name] ?? [] as $source) {
                if (str_starts_with($source, self::CLASS_SOURCE_PREFIX)) {
                    $className = substr($source, strlen(self::CLASS_SOURCE_PREFIX));
                    $classColumns[$className] ??= $this->getVisibleFieldClassColumnConfiguration($className, $user);
                    $column = $classColumns[$className][$name] ?? null;
                } else {
                    $elementColumns ??= $this->getElementColumnConfiguration();
                    $column = $elementColumns[$name] ?? null;
                }

                if ($column !== null) {
                    $result[$name] = $column;

                    break;
                }
            }
        }

        return $result;
    }

    /**
     * The asset grid columns by key: the system columns (file name, size, mime type, dates) and the metadata
     * columns, a predefined metadata definition taking precedence over a default metadata column of the
     * same name.
     *
     * @return array<string, ColumnConfiguration>
     */
    private function getElementColumnConfiguration(): array
    {
        $columns = [];

        foreach ($this->columnConfigurationService->getAvailableAssetColumnConfiguration() as $configuration) {
            $key = $configuration->getKey();

            if (!array_key_exists($key, $columns)
                || in_array(self::PREDEFINED_METADATA_GROUP, $configuration->getGroup(), true)
            ) {
                $columns[$key] = $configuration;
            }
        }

        return $columns;
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
