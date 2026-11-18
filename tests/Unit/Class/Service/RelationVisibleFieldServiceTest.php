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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\RelationVisibleFieldEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\RelationVisibleField;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\RelationVisibleFieldsConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\RelationVisibleFieldDefinitionFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\RelationVisibleFieldService;
use Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture\VisibleFieldsManyToManyRelation;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class RelationVisibleFieldServiceTest extends Unit
{
    public function testReturnsTheFieldsTheUnsavedConfigurationOffersInTheRelationsOrder(): void
    {
        $configuration = new RelationVisibleFieldsConfiguration(
            objectsAllowed: true,
            classes: ['Product'],
            assetsAllowed: true,
        );

        $receivedConfiguration = null;
        $factory = $this->makeEmpty(RelationVisibleFieldDefinitionFactoryInterface::class, [
            'create' => function (RelationVisibleFieldsConfiguration $configuration) use (&$receivedConfiguration) {
                $receivedConfiguration = $configuration;

                return new VisibleFieldsManyToManyRelation(stubAvailableVisibleFields: [
                    'creationDate' => [
                        'name' => 'creationDate',
                        'title' => 'Creation Date',
                        'fieldtype' => 'date',
                        'noteditable' => true,
                        'sources' => ['object', 'asset'],
                    ],
                    'color' => [
                        'name' => 'color',
                        'title' => 'Color',
                        'fieldtype' => 'select',
                        'noteditable' => true,
                        'optionsProviderClass' => null,
                        'options' => [['key' => 'Red', 'value' => 'red']],
                        'sources' => ['object:Product'],
                    ],
                    'name' => [
                        'name' => 'name',
                        'title' => '',
                        'fieldtype' => 'input',
                        'noteditable' => true,
                        'localized' => true,
                        'sources' => ['object:Product'],
                    ],
                    'copyright' => [
                        'name' => 'copyright',
                        'title' => 'copyright',
                        'fieldtype' => 'input',
                        'noteditable' => true,
                        'metadataType' => 'input',
                        'language' => 'en',
                        'sources' => ['asset'],
                    ],
                ]);
            },
        ]);

        $dispatched = [];
        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => function (object $event, ?string $eventName = null) use (&$dispatched) {
                $dispatched[] = [$event, $eventName];

                return $event;
            },
        ]);

        $fields = (new RelationVisibleFieldService($factory, $eventDispatcher))
            ->getAvailableVisibleFields($configuration);

        $this->assertSame($configuration, $receivedConfiguration);
        $this->assertSame(
            ['creationDate', 'color', 'name', 'copyright'],
            array_map(static fn (RelationVisibleField $field) => $field->getName(), $fields)
        );

        [$creationDate, $color, $name, $copyright] = $fields;

        $this->assertSame(['object', 'asset'], $creationDate->getSources());
        $this->assertSame('date', $creationDate->getFieldtype());
        $this->assertFalse($creationDate->isLocalized());

        $this->assertSame(['object:Product'], $color->getSources());
        $this->assertSame([['key' => 'Red', 'value' => 'red']], $color->getOptions());

        $this->assertTrue($name->isLocalized());
        $this->assertSame('name', $name->getTitle(), 'a field without a title is labelled with its name');

        $this->assertSame('input', $copyright->getMetadataType());
        $this->assertSame('en', $copyright->getLanguage());
        $this->assertNull($copyright->getOptions());

        $this->assertCount(4, $dispatched);
        $this->assertInstanceOf(RelationVisibleFieldEvent::class, $dispatched[0][0]);
        $this->assertSame(RelationVisibleFieldEvent::EVENT_NAME, $dispatched[0][1]);
        $this->assertSame($creationDate, $dispatched[0][0]->getRelationVisibleField());
    }

    public function testReturnsNothingWhenTheConfigurationOffersNoFields(): void
    {
        $factory = $this->makeEmpty(RelationVisibleFieldDefinitionFactoryInterface::class, [
            'create' => new VisibleFieldsManyToManyRelation(),
        ]);

        $service = new RelationVisibleFieldService($factory, $this->makeEmpty(EventDispatcherInterface::class));

        $this->assertSame([], $service->getAvailableVisibleFields(new RelationVisibleFieldsConfiguration()));
    }
}
