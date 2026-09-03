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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceService;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use PHPUnit\Framework\MockObject\MockObject;

final class InheritanceServiceTest extends Unit
{
    private const OBJECT_ID = 318;

    private const ANCESTOR_ID = 313;

    private const FIELD = 'carClass';

    public function testFieldWithoutDataAdapterIsNotInheritable(): void
    {
        $inheritanceData = $this->processFieldDefinition(
            $this->createFieldDefinition(supportsInheritance: true),
            withAdapter: false
        );

        $this->assertFalse($inheritanceData->isInheritable());
        $this->assertFalse($inheritanceData->isInherited());
        $this->assertSame(self::OBJECT_ID, $inheritanceData->getObjectId());
    }

    public function testFieldTypeThatDoesNotSupportInheritanceIsNotInheritable(): void
    {
        $inheritanceData = $this->processFieldDefinition(
            $this->createFieldDefinition(supportsInheritance: false)
        );

        $this->assertFalse($inheritanceData->isInheritable());
        $this->assertFalse($inheritanceData->isInherited());
    }

    /**
     * The case the flag exists for. Without it this is byte for byte what the two
     * tests above produce, so a client cannot tell a field carrying an own value
     * from one that can never take part in inheritance.
     */
    public function testFieldWithOwnValueIsInheritableButNotInherited(): void
    {
        $inheritanceData = $this->processFieldDefinition(
            $this->createFieldDefinition(supportsInheritance: true),
            ownValue: 'coupe'
        );

        $this->assertTrue($inheritanceData->isInheritable());
        $this->assertFalse($inheritanceData->isInherited());
        $this->assertSame(self::OBJECT_ID, $inheritanceData->getObjectId());
    }

    public function testFieldResolvedFromAnAncestorIsInheritedAndInheritable(): void
    {
        $inheritanceData = $this->processFieldDefinition(
            $this->createFieldDefinition(supportsInheritance: true),
            ownValue: null,
            ancestor: $this->createObject(self::ANCESTOR_ID, 'coupe')
        );

        $this->assertTrue($inheritanceData->isInheritable());
        $this->assertTrue($inheritanceData->isInherited());
        $this->assertSame(self::ANCESTOR_ID, $inheritanceData->getObjectId());
    }

    private function processFieldDefinition(
        Data $fieldDefinition,
        bool $withAdapter = true,
        ?string $ownValue = 'coupe',
        ?Concrete $ancestor = null
    ): InheritanceData {
        $dataAdapterService = $this->createMock(DataAdapterServiceInterface::class);
        $dataAdapterService->method('tryDataAdapter')->willReturn(
            $withAdapter ? $this->createMock(SetterDataInterface::class) : null
        );

        $service = new InheritanceService(
            $dataAdapterService,
            $this->createMock(DataObjectServiceResolverInterface::class)
        );

        $object = $this->createObject(self::OBJECT_ID, $ownValue);
        $object->method('getNextParentForInheritance')->willReturn($ancestor);

        $inheritanceData = $service->processFieldDefinition($object, $fieldDefinition, self::FIELD);

        $this->assertInstanceOf(InheritanceData::class, $inheritanceData);

        return $inheritanceData;
    }

    /**
     * @return Data&MockObject
     */
    private function createFieldDefinition(bool $supportsInheritance): Data
    {
        $fieldDefinition = $this->createMock(Data::class);
        $fieldDefinition->method('getFieldType')->willReturn('select');
        $fieldDefinition->method('supportsInheritance')->willReturn($supportsInheritance);
        $fieldDefinition->method('isEmpty')->willReturnCallback(
            static fn (mixed $value): bool => $value === null || $value === ''
        );

        return $fieldDefinition;
    }

    /**
     * @return Concrete&MockObject
     */
    private function createObject(int $id, ?string $value): Concrete
    {
        $object = $this->createMock(Concrete::class);
        $object->method('getId')->willReturn($id);
        $object->method('get')->willReturn($value);

        return $object;
    }
}
