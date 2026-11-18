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

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException as ApiParseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\DotNotationParserInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\RelationVisibleFieldValues;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\RelationVisibleFieldValuesRequest;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function in_array;
use function is_bool;
use function is_scalar;
use function sprintf;

/**
 * Resolves the visible field values of the rows of the generic relation types.
 *
 * Which fields apply to a row is decided by the relation ({@see ManyToManyRelation::getVisibleFieldSources()}).
 * A class field of a related object is resolved the way the object relation grids resolve it - through the
 * grid column resolvers, with the user's column configuration of the object's class - so its value has the
 * format the data object cells render, and a field the user may not see in that class stays empty. The
 * properties of every element and the asset fields are resolved by the relation itself
 * ({@see ManyToManyRelation::getVisibleFieldData()}).
 *
 * @internal
 */
final readonly class RelationVisibleFieldValuesService implements RelationVisibleFieldValuesServiceInterface
{
    private const string CLASS_SOURCE_PREFIX = 'object:';

    private const string DATA_OBJECT_ADAPTER_TYPE = 'dataobject.adapter';

    /**
     * Data object cells that can render a plain value (string, number, boolean or timestamp), which is what
     * the relation resolves for properties and asset fields.
     */
    private const array SCALAR_DATA_OBJECT_FRONTEND_TYPES = [
        'checkbox',
        'country',
        'date',
        'datetime',
        'email',
        'firstname',
        'gender',
        'input',
        'language',
        'lastname',
        'numeric',
        'select',
        'slider',
        'textarea',
        'time',
        'wysiwyg',
    ];

    public function __construct(
        private ColumnConfigurationForRelationServiceInterface $columnConfigurationForRelationService,
        private DotNotationParserInterface $dotNotationParser,
        private GridServiceInterface $gridService,
        private LocalizedFieldResolverInterface $localizedFieldResolver,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibleFieldValues(RelationVisibleFieldValuesRequest $request, UserInterface $user): array
    {
        $elements = $request->getElements();
        $fieldDefinition = $this->getRelationFieldDefinition($request);

        if ($elements === []) {
            return [];
        }

        $columns = $this->columnConfigurationForRelationService->getAvailableDataObjectColumnConfigurationForRelation(
            $request->getClassId(),
            $request->getRelationField(),
            $user
        );

        if ($columns === []) {
            return [];
        }

        $sources = $fieldDefinition->getVisibleFieldSources();
        $classColumns = [];

        // the object relation grids show the fallback language values of empty localized fields, too
        $fallbackBackup = $this->localizedFieldResolver->getGetFallbackValues();
        $this->localizedFieldResolver->setGetFallbackValues(true);

        try {
            $result = [];
            foreach ($elements as $reference) {
                $element = $this->serviceResolver->getElementById($reference['type'], $reference['id']);

                if ($element === null) {
                    continue;
                }

                try {
                    $this->securityService->hasElementPermission(
                        $element,
                        $user,
                        ElementPermissions::VIEW_PERMISSION
                    );
                } catch (ForbiddenException) {
                    // no values of elements the user may not view; the row only shows what it already has
                    continue;
                }

                $result[] = new RelationVisibleFieldValues(
                    $reference['type'],
                    $reference['id'],
                    $this->resolveRow($fieldDefinition, $columns, $sources, $classColumns, $element, $request, $user)
                );
            }

            return $result;
        } finally {
            $this->localizedFieldResolver->setGetFallbackValues($fallbackBackup);
        }
    }

    /**
     * @throws InvalidArgumentException|ApiParseException
     */
    private function getRelationFieldDefinition(RelationVisibleFieldValuesRequest $request): ManyToManyRelation
    {
        try {
            $fieldDefinition = $this->dotNotationParser
                ->parseByClassId($request->getClassId(), $request->getRelationField())
                ->getFieldDefinition();
        } catch (ParseException $exception) {
            throw new ApiParseException($exception->getMessage());
        }

        if (!$fieldDefinition instanceof ManyToManyRelation) {
            throw new InvalidArgumentException(
                sprintf(
                    'Field %s is not a ManyToManyRelation or AdvancedManyToManyRelation',
                    $request->getRelationField()
                )
            );
        }

        return $fieldDefinition;
    }

    /**
     * @param array<string, ColumnConfiguration> $columns
     * @param array<string, string[]> $sources
     * @param array<string, array<string, ColumnConfiguration>> $classColumns
     *
     * @return ColumnData[]
     */
    private function resolveRow(
        ManyToManyRelation $fieldDefinition,
        array $columns,
        array $sources,
        array &$classColumns,
        ElementInterface $element,
        RelationVisibleFieldValuesRequest $request,
        UserInterface $user
    ): array {
        $className = $element instanceof Concrete ? $element->getClassName() : null;
        $classFieldColumns = [];
        $relationFieldNames = [];

        foreach ($columns as $name => $column) {
            if ($className !== null
                && in_array(self::CLASS_SOURCE_PREFIX . $className, $sources[$name] ?? [], true)
            ) {
                $classColumns[$className] ??= $this->columnConfigurationForRelationService
                    ->getVisibleFieldClassColumnConfiguration($className, $user);
                $classColumn = $classColumns[$className][$name] ?? null;

                // the field must be visible to the user in this class, and of the type the column renders
                if ($classColumn !== null
                    && $column->getType() === self::DATA_OBJECT_ADAPTER_TYPE
                    && $classColumn->getFrontendType() === $column->getFrontendType()
                ) {
                    $classFieldColumns[$name] = $classColumn;
                }

                continue;
            }

            $relationFieldNames[] = $name;
        }

        $classFieldData = $classFieldColumns !== [] && $element instanceof Concrete
            ? $this->resolveClassFields($element, $classFieldColumns, $request->getLocale(), $user)
            : [];
        $relationValues = $relationFieldNames !== []
            ? $this->resolveRelationFields($fieldDefinition, $relationFieldNames, $element, $request->getLocale())
            : [];

        $data = [];
        foreach ($columns as $name => $column) {
            $data[] = $classFieldData[$name] ?? new ColumnData(
                key: $name,
                locale: $column->isLocalizable() ? $request->getLocale() : null,
                value: $this->toColumnValue($column, $relationValues[$name] ?? null),
                fieldType: $column->getType(),
            );
        }

        return $data;
    }

    /**
     * @param array<string, ColumnConfiguration> $classFieldColumns
     *
     * @return array<string, ColumnData>
     */
    private function resolveClassFields(
        Concrete $object,
        array $classFieldColumns,
        ?string $locale,
        UserInterface $user
    ): array {
        $columns = [];
        foreach ($classFieldColumns as $name => $configuration) {
            $columns[] = new Column(
                key: $name,
                locale: $configuration->isLocalizable() ? $locale : null,
                type: $configuration->getType(),
                group: $configuration->getGroup(),
                config: [],
            );
        }

        $gridData = $this->gridService->getGridDataForElement(
            new ColumnCollection($columns),
            null,
            ElementTypes::TYPE_OBJECT,
            (int) $object->getId(),
            false,
            $user,
            $object
        );

        $data = [];
        foreach ($gridData['columns'] ?? [] as $columnData) {
            if ($columnData instanceof ColumnData) {
                $data[$columnData->getKey()] = $columnData;
            }
        }

        return $data;
    }

    /**
     * Resolves only the given names, so the class fields of an object are not resolved a second time in the
     * edit mode format the data object cells do not read.
     *
     * @param string[] $names
     *
     * @return array<string, mixed>
     */
    private function resolveRelationFields(
        ManyToManyRelation $fieldDefinition,
        array $names,
        ElementInterface $element,
        ?string $locale
    ): array {
        $subset = clone $fieldDefinition;
        $subset->setVisibleFields($names);

        return $subset->getVisibleFieldData($element, ['language' => $locale]);
    }

    /**
     * A column described by a class field renders the value with that field's data object cell; a property or
     * asset field sharing its name can only be shown there if the cell renders plain values.
     */
    private function toColumnValue(ColumnConfiguration $column, mixed $value): mixed
    {
        if ($value === null || $column->getType() !== self::DATA_OBJECT_ADAPTER_TYPE) {
            return $value;
        }

        if (!is_scalar($value) || (is_bool($value) && $column->getFrontendType() !== 'checkbox')) {
            return null;
        }

        return in_array($column->getFrontendType(), self::SCALAR_DATA_OBJECT_FRONTEND_TYPES, true) ? $value : null;
    }
}
