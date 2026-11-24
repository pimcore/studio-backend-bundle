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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Version\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Version\Service\DeletedRelationService;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\Version\UnresolvedReference;

/**
 * Relations of a version to elements deleted afterwards are shown as deleted entries
 * (pimcore/service-operations#1286).
 *
 * @internal
 */
final class DeletedRelationServiceTest extends Unit
{
    public function testSingleRelationToDeletedElementIsShownAsDeletedEntry(): void
    {
        $result = (new DeletedRelationService())->addDeletedRelations(
            ['asset' => null, 'title' => 'Title'],
            $this->createClass(),
            [new UnresolvedReference('asset', null, null, 'asset', 12)]
        );

        $this->assertSame(
            ['asset' => $this->deletedEntry('asset', 12), 'title' => 'Title'],
            $result
        );
    }

    public function testDeletedEntriesKeepTheirPositionInManyToManyRelations(): void
    {
        $first = new RelatedElementData(1, 'object', 'Car', '/cars/first');
        $third = new RelatedElementData(3, 'object', 'Car', '/cars/third');

        $result = (new DeletedRelationService())->addDeletedRelations(
            ['relations' => [$first, $third], 'objects' => null],
            $this->createClass(),
            [
                new UnresolvedReference('relations', null, 1, 'document', 2),
                new UnresolvedReference('relations', null, 3, 'asset', 4),
                new UnresolvedReference('objects', null, 0, 'object', 5),
            ]
        );

        $this->assertEquals(
            [
                'relations' => [$first, $this->deletedEntry('document', 2), $third, $this->deletedEntry('asset', 4)],
                'objects' => [$this->deletedEntry('object', 5)],
            ],
            $result
        );
    }

    public function testRelationInLocalizedFieldsIsShownForItsLanguage(): void
    {
        $result = (new DeletedRelationService())->addDeletedRelations(
            ['localizedfields' => ['lrelation' => ['en' => null, 'de' => null]]],
            $this->createClass(),
            [new UnresolvedReference('lrelation', 'de', null, 'object', 7)]
        );

        $this->assertSame(
            ['localizedfields' => ['lrelation' => ['en' => null, 'de' => $this->deletedEntry('object', 7)]]],
            $result
        );
    }

    public function testReferencesOfUnsupportedOrUnknownFieldsAreIgnored(): void
    {
        $objectData = ['advanced' => [], 'title' => 'Title', 'localizedfields' => ['lrelation' => ['en' => null]]];

        $result = (new DeletedRelationService())->addDeletedRelations(
            $objectData,
            $this->createClass(),
            [
                new UnresolvedReference('advanced', null, 0, 'object', 1),
                new UnresolvedReference('title', null, null, 'object', 2),
                new UnresolvedReference('removedField', null, null, 'object', 3),
                new UnresolvedReference('lrelation', 'fr', null, 'object', 4),
            ]
        );

        $this->assertSame($objectData, $result);
    }

    private function createClass(): ClassDefinition
    {
        $localizedfields = new Localizedfields();
        $localizedfields->setName('localizedfields');
        $localizedfields->setChildren([(new ManyToOneRelation())->setName('lrelation')]);

        return (new ClassDefinition())
            ->setId('test')
            ->addFieldDefinition('asset', (new ManyToOneRelation())->setName('asset'))
            ->addFieldDefinition('relations', (new ManyToManyRelation())->setName('relations'))
            ->addFieldDefinition('objects', (new ManyToManyObjectRelation())->setName('objects'))
            ->addFieldDefinition('advanced', (new AdvancedManyToManyRelation())->setName('advanced'))
            ->addFieldDefinition('title', (new Input())->setName('title'))
            ->addFieldDefinition('localizedfields', $localizedfields);
    }

    private function deletedEntry(string $type, int $id): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'subtype' => '',
            'fullPath' => '',
            'isPublished' => null,
            'hasViewAccess' => false,
            'isDeleted' => true,
        ];
    }
}
