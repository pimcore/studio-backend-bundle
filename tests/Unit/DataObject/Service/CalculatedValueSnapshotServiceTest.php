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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\CalculatedValueSnapshotService;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue as CalculatedValueDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue;
use Pimcore\Model\DataObject\Service as DataObjectService;

/**
 * @internal
 */
final class CalculatedValueSnapshotServiceTest extends Unit
{
    public function testAnObjectThatIsNotRestoredFromAVersionStoresNothing(): void
    {
        $service = new CalculatedValueSnapshotService();
        $object = $this->makeEmpty(Concrete::class, ['__getCalculatedValueSnapshot' => null]);

        $this->assertFalse($service->hasStoredValue($object, $this->objectLevelContext('total')));
        $this->assertNull($service->getStoredValue($object, $this->objectLevelContext('total')));
    }

    public function testStoredValuesAreFoundUnderTheKeyOfTheGeneratedGetters(): void
    {
        $service = new CalculatedValueSnapshotService();
        $object = $this->makeEmpty(Concrete::class, [
            '__getCalculatedValueSnapshot' => [
                DataObjectService::getCalculatedValueSnapshotKey($this->objectLevelContext('total')) => '42 EUR',
                DataObjectService::getCalculatedValueSnapshotKey($this->localizedContext('total', 'de')) => '42 €',
                DataObjectService::getCalculatedValueSnapshotKey($this->objectLevelContext('nothing')) => null,
            ],
        ]);

        $this->assertTrue($service->hasStoredValue($object, $this->objectLevelContext('total')));
        $this->assertSame('42 EUR', $service->getStoredValue($object, $this->objectLevelContext('total')));

        $this->assertTrue($service->hasStoredValue($object, $this->localizedContext('total', 'de')));
        $this->assertSame('42 €', $service->getStoredValue($object, $this->localizedContext('total', 'de')));
        $this->assertFalse($service->hasStoredValue($object, $this->localizedContext('total', 'en')));

        // a stored null is a value, a field the version does not store is not
        $this->assertTrue($service->hasStoredValue($object, $this->objectLevelContext('nothing')));
        $this->assertNull($service->getStoredValue($object, $this->objectLevelContext('nothing')));
        $this->assertFalse($service->hasStoredValue($object, $this->objectLevelContext('missing')));
    }

    public function testAnOwnerNameOnAnObjectLevelContextDoesNotMatchWhatTheVersionStores(): void
    {
        // the generated getters identify object-level fields without an owner name, and so does the version
        $service = new CalculatedValueSnapshotService();
        $object = $this->makeEmpty(Concrete::class, [
            '__getCalculatedValueSnapshot' => [
                DataObjectService::getCalculatedValueSnapshotKey($this->objectLevelContext('total')) => '42 EUR',
            ],
        ]);

        $context = new CalculatedValue('total');
        $context->setContextualData('object', 'total', null, null, null, null, $this->definition('total'));

        $this->assertFalse($service->hasStoredValue($object, $context));
    }

    private function objectLevelContext(string $name): CalculatedValue
    {
        $context = new CalculatedValue($name);
        $context->setContextualData('object', null, null, null, null, null, $this->definition($name));

        return $context;
    }

    private function localizedContext(string $name, string $language): CalculatedValue
    {
        $context = new CalculatedValue($name);
        $context->setContextualData('localizedfield', 'localizedfields', null, $language, null, null, $this->definition($name));

        return $context;
    }

    private function definition(string $name): CalculatedValueDefinition
    {
        $definition = new CalculatedValueDefinition();
        $definition->setName($name);

        return $definition;
    }
}
