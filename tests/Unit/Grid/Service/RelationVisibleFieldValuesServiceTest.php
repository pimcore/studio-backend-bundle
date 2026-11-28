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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\DotNotationParserInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\RelationVisibleFieldValues;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\RelationVisibleFieldValuesRequest;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationForRelationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\RelationVisibleFieldValuesService;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture\VisibleFieldsManyToManyRelation;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class RelationVisibleFieldValuesServiceTest extends Unit
{
    public function testResolvesTheVisibleFieldsThatApplyToEachRow(): void
    {
        $fieldDefinition = new VisibleFieldsManyToManyRelation(
            stubSources: [
                'creationDate' => ['object', 'asset'],
                'color' => ['object:Product'],
                'filename' => ['asset'],
                'copyright' => ['asset'],
            ],
            stubAssetMetadata: ['copyright' => 'Studio Pictures'],
        );
        $fieldDefinition->setVisibleFields('creationDate,color,filename,copyright');

        $asset = new Asset\Image();
        $asset->setId(83);
        $asset->setFilename('chair.jpg');
        $asset->setMimeType('image/jpeg');
        $asset->setCreationDate(1700000000);

        $product = $this->createObject(7, 'Product', 1700000100);
        $category = $this->createObject(8, 'Category', 1700000200);

        $folder = new Folder();
        $folder->setId(9);
        $folder->setCreationDate(1700000300);

        $forbiddenAsset = new Asset\Image();
        $forbiddenAsset->setId(84);

        $elements = [
            'asset:83' => $asset,
            'object:7' => $product,
            'object:8' => $category,
            'object:9' => $folder,
            'asset:84' => $forbiddenAsset,
        ];

        $gridRequests = [];
        $fallbackValues = [];

        $service = $this->createService(
            fieldDefinition: $fieldDefinition,
            columns: [
                'creationDate' => $this->createColumn('creationDate', 'system.datetime', 'datetime'),
                'color' => $this->createColumn('color', 'dataobject.adapter', 'select'),
                'filename' => $this->createColumn('filename', 'system.string', 'input'),
                'copyright' => $this->createColumn('copyright', 'metadata.input', 'input', localizable: true),
            ],
            classColumns: [
                'Product' => ['color' => $this->createColumn('color', 'dataobject.adapter', 'select')],
            ],
            elements: $elements,
            gridService: $this->makeEmpty(GridServiceInterface::class, [
                'getGridDataForElement' => function (
                    ColumnCollection $columns,
                    $studioElement,
                    string $elementType,
                    int $elementId,
                    bool $isExport,
                    ?UserInterface $user,
                    ?ElementInterface $coreElement
                ) use (&$gridRequests, $product): array {
                    $gridRequests[] = [$columns, $elementType, $elementId, $isExport, $coreElement];
                    $this->assertSame($product, $coreElement);

                    return [
                        'id' => $elementId,
                        'columns' => [new ColumnData('color', null, 'red', 'dataobject.adapter')],
                    ];
                },
            ]),
            fallbackValues: $fallbackValues,
        );

        $result = $service->getVisibleFieldValues(
            new RelationVisibleFieldValuesRequest(
                classId: 'CAR',
                relationField: 'related',
                locale: 'de',
                elements: [
                    ['type' => 'asset', 'id' => 83],
                    ['type' => 'data-object', 'id' => 7],
                    ['type' => 'object', 'id' => 8],
                    ['type' => 'object', 'id' => 9],
                    ['type' => 'asset', 'id' => 84],
                    ['type' => 'document', 'id' => 99],
                    ['type' => 'asset', 'id' => 83],
                ]
            ),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertSame(
            ['asset:83', 'object:7', 'object:8', 'object:9'],
            array_map(static fn (RelationVisibleFieldValues $row) => $row->getType() . ':' . $row->getId(), $result),
            'elements the user may not view and missing elements are left out, duplicates requested once'
        );

        [$assetRow, $productRow, $categoryRow, $folderRow] = array_map($this->valuesOf(...), $result);

        $this->assertSame(
            ['creationDate' => 1700000000, 'color' => null, 'filename' => 'chair.jpg', 'copyright' => 'Studio Pictures'],
            $assetRow
        );
        $this->assertSame(
            ['creationDate' => 1700000100, 'color' => 'red', 'filename' => null, 'copyright' => null],
            $productRow,
            'an object of an allowed class has its class field and the common properties'
        );
        $this->assertSame(
            ['creationDate' => 1700000200, 'color' => null, 'filename' => null, 'copyright' => null],
            $categoryRow,
            'an object of another class only has the common properties'
        );
        $this->assertSame(
            ['creationDate' => 1700000300, 'color' => null, 'filename' => null, 'copyright' => null],
            $folderRow,
            'an object folder only has the common properties'
        );

        $this->assertSame('de', $fieldDefinition->requestedMetadataLanguages['copyright']);
        $this->assertSame('de', $result[0]->getColumns()[3]->getLocale(), 'a localized column carries the language');
        $this->assertNull($result[0]->getColumns()[0]->getLocale());

        $this->assertCount(1, $gridRequests, 'class fields are resolved for the object of the allowed class only');
        [$columns, $elementType, $elementId, $isExport] = $gridRequests[0];
        $this->assertSame('object', $elementType);
        $this->assertSame(7, $elementId);
        $this->assertFalse($isExport);
        $this->assertSame(['color'], array_map(static fn ($column) => $column->getKey(), $columns->getColumns()));
        $this->assertSame('dataobject.adapter', $columns->getColumns()[0]->getType());

        $this->assertSame([true, false], $fallbackValues, 'fallback languages are applied and then restored');
    }

    public function testClassFieldsTheUserMayNotSeeOrOfAnotherTypeStayEmpty(): void
    {
        $fieldDefinition = new VisibleFieldsManyToManyRelation(
            stubSources: [
                'color' => ['object:Product', 'object:Category', 'object:Shelf'],
                'title' => ['object:Product', 'asset'],
                'picture' => ['object:Product', 'asset'],
            ],
            stubAssetMetadata: ['title' => 'A chair', 'picture' => '/pictures/chair.jpg'],
        );
        $fieldDefinition->setVisibleFields('color,title,picture');

        $asset = new Asset\Image();
        $asset->setId(83);

        $service = $this->createService(
            fieldDefinition: $fieldDefinition,
            columns: [
                'color' => $this->createColumn('color', 'dataobject.adapter', 'select'),
                'title' => $this->createColumn('title', 'dataobject.adapter', 'input'),
                'picture' => $this->createColumn('picture', 'dataobject.adapter', 'image'),
            ],
            classColumns: [
                // a field of the same name but another type
                'Category' => ['color' => $this->createColumn('color', 'dataobject.adapter', 'input')],
                // the user may not see the field in this class
                'Shelf' => [],
            ],
            elements: [
                'object:8' => $this->createObject(8, 'Category', 1),
                'object:10' => $this->createObject(10, 'Shelf', 1),
                'asset:83' => $asset,
            ],
            gridService: $this->makeEmpty(GridServiceInterface::class, [
                'getGridDataForElement' => Expected::never(),
            ]),
        );

        $result = $service->getVisibleFieldValues(
            new RelationVisibleFieldValuesRequest(
                classId: 'CAR',
                relationField: 'related',
                elements: [
                    ['type' => 'object', 'id' => 8],
                    ['type' => 'object', 'id' => 10],
                    ['type' => 'asset', 'id' => 83],
                ]
            ),
            $this->makeEmpty(UserInterface::class)
        );

        [$categoryRow, $shelfRow, $assetRow] = array_map($this->valuesOf(...), $result);

        $this->assertSame(['color' => null, 'title' => null, 'picture' => null], $categoryRow);
        $this->assertSame(['color' => null, 'title' => null, 'picture' => null], $shelfRow);
        $this->assertSame(
            ['color' => null, 'title' => 'A chair', 'picture' => null],
            $assetRow,
            'a plain value is shown in a class field column only if its cell renders plain values'
        );
    }

    public function testReturnsNothingWithoutColumnsOrElements(): void
    {
        $fieldDefinition = new VisibleFieldsManyToManyRelation(['creationDate' => ['object']]);
        $request = new RelationVisibleFieldValuesRequest(
            classId: 'CAR',
            relationField: 'related',
            elements: [['type' => 'object', 'id' => 7]]
        );
        $user = $this->makeEmpty(UserInterface::class);

        $withoutColumns = $this->createService(
            fieldDefinition: $fieldDefinition,
            columns: [],
            classColumns: [],
            elements: [],
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => Expected::never(),
            ]),
        );
        $this->assertSame([], $withoutColumns->getVisibleFieldValues($request, $user));

        $withoutElements = $this->createService(
            fieldDefinition: $fieldDefinition,
            columns: ['creationDate' => $this->createColumn('creationDate', 'system.datetime', 'datetime')],
            classColumns: [],
            elements: [],
        );
        $this->assertSame(
            [],
            $withoutElements->getVisibleFieldValues(
                new RelationVisibleFieldValuesRequest(classId: 'CAR', relationField: 'related'),
                $user
            )
        );
    }

    public function testRejectsOtherFieldTypes(): void
    {
        $service = $this->createService(
            fieldDefinition: new ManyToManyObjectRelation(),
            columns: [],
            classColumns: [],
            elements: [],
        );

        $this->expectException(InvalidArgumentException::class);

        $service->getVisibleFieldValues(
            new RelationVisibleFieldValuesRequest(classId: 'CAR', relationField: 'related'),
            $this->makeEmpty(UserInterface::class)
        );
    }

    public function testRejectsTooManyElementsAndMalformedReferences(): void
    {
        $tooMany = new RelationVisibleFieldValuesRequest(
            classId: 'CAR',
            relationField: 'related',
            elements: array_fill(0, RelationVisibleFieldValuesRequest::MAX_ELEMENTS + 1, ['type' => 'asset', 'id' => 1])
        );

        try {
            $tooMany->getElements();
            $this->fail('expected the request to be rejected');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        (new RelationVisibleFieldValuesRequest(
            classId: 'CAR',
            relationField: 'related',
            elements: [['type' => 'user', 'id' => 1]]
        ))->getElements();
    }

    /**
     * @param array<string, ColumnConfiguration> $columns
     * @param array<string, array<string, ColumnConfiguration>> $classColumns
     * @param array<string, ElementInterface> $elements
     * @param bool[] $fallbackValues
     */
    private function createService(
        Data $fieldDefinition,
        array $columns,
        array $classColumns,
        array $elements,
        ?GridServiceInterface $gridService = null,
        ?ServiceResolverInterface $serviceResolver = null,
        array &$fallbackValues = [],
    ): RelationVisibleFieldValuesService {
        return new RelationVisibleFieldValuesService(
            $this->makeEmpty(ColumnConfigurationForRelationServiceInterface::class, [
                'getAvailableDataObjectColumnConfigurationForRelation' => $columns,
                'getVisibleFieldClassColumnConfiguration' => static fn (string $className) => $classColumns[$className] ?? [],
            ]),
            $this->makeEmpty(DotNotationParserInterface::class, [
                'parseByClassId' => new FieldDefinitionWrapper($fieldDefinition, 'object', 'related'),
            ]),
            $gridService ?? $this->makeEmpty(GridServiceInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class, [
                'getGetFallbackValues' => false,
                'setGetFallbackValues' => static function (bool $value) use (&$fallbackValues): void {
                    $fallbackValues[] = $value;
                },
            ]),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'hasElementPermission' => static function (ElementInterface $element): void {
                    if ($element->getId() === 84) {
                        throw new ForbiddenException();
                    }
                },
            ]),
            $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => static fn (string $type, int|string $id) => $elements[$type . ':' . $id] ?? null,
            ]),
        );
    }

    private function createObject(int $id, string $className, int $creationDate): Concrete
    {
        $object = new Concrete();
        $object->setId($id);
        $object->setClassName($className);
        $object->setCreationDate($creationDate);

        return $object;
    }

    private function createColumn(
        string $key,
        string $type,
        string $frontendType,
        bool $localizable = false
    ): ColumnConfiguration {
        return new ColumnConfiguration(
            key: $key,
            group: ['test'],
            sortable: false,
            editable: false,
            exportable: false,
            filterable: false,
            localizable: $localizable,
            locale: null,
            type: $type,
            frontendType: $frontendType,
            config: [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function valuesOf(RelationVisibleFieldValues $row): array
    {
        $values = [];
        foreach ($row->getColumns() as $column) {
            $values[$column->getKey()] = $column->getValue();
        }

        return $values;
    }
}
