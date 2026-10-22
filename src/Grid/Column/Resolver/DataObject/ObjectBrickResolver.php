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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ExportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ObjectBrickKey;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\ObjectBrick\Service\ObjectBrickServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_key_exists;
use function count;
use function implode;

/**
 * @internal
 */
final class ObjectBrickResolver implements
    ColumnResolverInterface,
    CoreElementColumnResolverInterface,
    ExportResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;

    /**
     * Every brick column of a grid row normalizes the same brick container (all bricks, all attributes,
     * all locales) and processes its inheritance, and both results are identical for every column of
     * that row. A grid resolves one row at a time, so the results are kept for the element currently
     * being resolved and dropped as soon as another element or another revision of it comes along.
     */
    private ?Concrete $cachedElement = null;

    private string $cachedRevision = '';

    /** @var array<string, mixed> normalized brick container per brick field */
    private array $normalizedContainers = [];

    /** @var array<string, mixed> inheritance data collection per brick field */
    private array $inheritanceDataCollections = [];

    public function __construct(
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver,
        private readonly DataServiceInterface $dataService,
        private readonly InheritanceServiceInterface $inheritanceService,
        private readonly DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private readonly ObjectBrickServiceInterface $objectBrickService,
        private readonly DefinitionResolverInterface $definitionResolver,
        private readonly ToolResolverInterface $toolResolver,
        private readonly LocalizedFieldResolverInterface $localizedFieldResolver,
    ) {
    }

    /** @see LocalizedValueTrait::doGetFallbackValues() */
    protected function doGetFallbackValues(): bool
    {
        return $this->localizedFieldResolver->doGetFallbackValues();
    }

    /** @see LocalizedValueTrait::getDefaultLanguage() */
    protected function getDefaultLanguage(): ?string
    {
        return $this->toolResolver->getDefaultLanguage();
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
    {
        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        $objectBrickKey = $this->mapObjectBrickKey($column->getKey());

        $classDefinition = $this->classDefinitionResolver->getById($element->getClassId());
        $fieldDefinition = $this->getFieldDefinition($objectBrickKey->getField(), $classDefinition);

        $value = $this->getNormalizedContainer($element, $objectBrickKey->getField(), $fieldDefinition);

        $objectBrickFieldType = $this->objectBrickService->findObjectBrickField(
            $objectBrickKey->getBrickName(),
            $objectBrickKey->getAttribute(),
        )->getFieldType();

        if ($value === []) {
            return $this->getColumnData(
                $column,
                null,
                $objectBrickFieldType
            );
        }

        $inheritanceData = null;
        if ($classDefinition->getAllowInherit() && $fieldDefinition->supportsInheritance()) {
            try {
                $inheritanceData = $this->getInheritanceData($element, $fieldDefinition, $objectBrickKey, $column);
            } catch (NotFoundException) {
                // inheritance data not found (field not set in parent id)
            }
        }

        try {
            $returnValue = null;
            $brickName = $objectBrickKey->getBrickName();
            $attribute = $objectBrickKey->getAttribute();

            if ($column->getLocale()) {
                $returnValue = $value[$brickName]['localizedfields'][$attribute][$column->getLocale()] ?? null;
            }

            if (!$column->getLocale()) {
                $returnValue = $value[$brickName][$attribute] ?? null;
            }
        } catch (Exception) {
            $value = null;
        }

        return $this->getColumnData(
            $column,
            $returnValue,
            $objectBrickFieldType,
            $inheritanceData
        );
    }

    public function resolveForExport(Column $column, ElementInterface $element, UserInterface $user): ColumnData
    {
        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        try {
            $objectBrickKey = $this->mapObjectBrickKey($column->getKey());

            $brickClass = $this->definitionResolver->getByKey($objectBrickKey->getBrickName());
            $fieldDefinition = $brickClass?->getFieldDefinition($objectBrickKey->getAttribute());

            // A stale column (brick or attribute removed from the definition) gives an empty cell
            if (!$fieldDefinition) {
                return $this->getColumnData($column, null, $column->getType());
            }

            $brickContainer = $element->get($objectBrickKey->getField());
            if (!$brickContainer) {
                return $this->getColumnData($column, null, $fieldDefinition->getFieldType());
            }

            $brick = $brickContainer->get($objectBrickKey->getBrickName());

            // Callers may pass a default locale for every column; only localized attributes live in localizedfields
            $locale = $this->isLocalizedAttribute($brickClass, $objectBrickKey->getAttribute())
                ? $column->getLocale()
                : null;

            if ($locale) {
                $brick = $brick?->get('localizedfields');
            }

            if (!$brick) {
                return $this->getColumnData($column, null, $fieldDefinition->getFieldType());
            }

            $context = new FieldContextData(
                contextObject: $brick,
                legacyParameters: ['context' => [
                    'containerType' => 'objectbrick',
                    'containerKey' => $objectBrickKey->getBrickName(),
                    'fieldname' => $objectBrickKey->getAttribute(),
                ],
                'language' => $locale,
            ]
            );

            $value = $this->dataService->getExportFieldValue(
                $element,
                $fieldDefinition,
                $objectBrickKey->getField(),
                $context
            );

            return $this->getColumnData($column, $value, $fieldDefinition->getFieldType());

        } catch (Exception) {
            return $this->getColumnData($column, null, $column->getType());
        }
    }

    public function getType(): string
    {
        return 'dataobject.objectbrick';
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_OBJECT,
        ];
    }

    private function isLocalizedAttribute(Definition $brickClass, string $attribute): bool
    {
        $localizedFields = $brickClass->getFieldDefinition('localizedfields');

        return $localizedFields instanceof Localizedfields
            && $localizedFields->getFieldDefinition($attribute) !== null;
    }

    /**
     * @throws Exception
     * @throws NotFoundException
     */
    private function getFieldDefinition(string $field, ClassDefinition $classDefinition): Data
    {
        $fieldDefinition = $classDefinition->getFieldDefinition($field);

        if (!$fieldDefinition instanceof Data) {
            throw new NotFoundException('Field definition', $field);
        }

        return $fieldDefinition;
    }

    /**
     * @throws NotFoundException
     */
    private function getInheritanceData(
        Concrete $element,
        Data $fieldDefinition,
        ObjectBrickKey $key,
        Column $column
    ): InheritanceData {
        $inheritanceDataCollection = $this->getInheritanceDataCollection($element, $key->getField(), $fieldDefinition);

        try {
            $inheritanceData = null;
            $brickName = $key->getBrickName();
            $attribute = $key->getAttribute();
            $locale = $column->getLocale();

            if ($locale) {
                $inheritanceData =
                    $inheritanceDataCollection[$brickName]['localizedfields'][$attribute][$locale] ?? null;
            }

            if (!$locale) {
                $inheritanceData = $inheritanceDataCollection[$brickName][$attribute] ?? null;
            }

            if (!$inheritanceData instanceof InheritanceData) {
                throw new Exception();
            }

        } catch (Exception) {
            throw new NotFoundException('Inheritance data', $key->getBrickName() . '.' . $key->getAttribute());
        }

        return $inheritanceData;
    }

    private function getNormalizedContainer(Concrete $element, string $field, Data $fieldDefinition): mixed
    {
        $this->useCacheOf($element);

        if (!array_key_exists($field, $this->normalizedContainers)) {
            $this->normalizedContainers[$field] = $this->dataService->getNormalizedValue(
                $this->getLocalizedValueFromKey($field, null, $element),
                $fieldDefinition
            );
        }

        return $this->normalizedContainers[$field];
    }

    private function getInheritanceDataCollection(Concrete $element, string $field, Data $fieldDefinition): mixed
    {
        $this->useCacheOf($element);

        if (!array_key_exists($field, $this->inheritanceDataCollections)) {
            $this->inheritanceDataCollections[$field] = $this->dataObjectServiceResolver->useInheritedValues(
                false,
                function () use ($element, $fieldDefinition, $field) {
                    return $this->inheritanceService->processFieldDefinition($element, $fieldDefinition, $field);
                }
            );
        }

        return $this->inheritanceDataCollections[$field];
    }

    /**
     * Keeps the cache only for the given element: modification date and version count change when the
     * element is saved in between, the fallback-language flag changes what the localized getters return.
     */
    private function useCacheOf(Concrete $element): void
    {
        $revision = implode('|', [
            (int) $this->doGetFallbackValues(),
            (int) $element->getModificationDate(),
            $element->getVersionCount(),
        ]);

        if ($element === $this->cachedElement && $revision === $this->cachedRevision) {
            return;
        }

        $this->cachedElement = $element;
        $this->cachedRevision = $revision;
        $this->normalizedContainers = [];
        $this->inheritanceDataCollections = [];
    }

    /**
     * @throws InvalidArgumentException
     */
    private function mapObjectBrickKey(string $key): ObjectBrickKey
    {
        $split = explode('.', $key);
        if (count($split) !== 3) {
            throw new InvalidArgumentException(
                'invalid key structure for object brick.
                          Is has to be in the format of <field>.<brickname>.<attributeofbrick>'
            );
        }

        return new ObjectBrickKey(
            field: $split[0],
            brickName: $split[1],
            attribute: $split[2]
        );
    }
}
