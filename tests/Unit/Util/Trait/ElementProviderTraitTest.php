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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Util\Trait;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
use ReflectionClass;
use ReflectionException;

/**
 * @internal
 */
final class ElementProviderTraitTest extends Unit
{
    public function testGetVersionDataReturnsElementWithoutVersion(): void
    {
        $element = $this->createMock(Concrete::class);

        $this->assertSame($element, $this->createTraitHelper()->callGetVersionData($element, null));
    }

    /**
     * @throws ReflectionException
     */
    public function testGetVersionDataReturnsVersionData(): void
    {
        $element = $this->createMock(Concrete::class);
        $versionElement = $this->createMock(Concrete::class);
        $version = $this->createVersion(null);
        $version->setData($versionElement);

        $this->assertSame($versionElement, $this->createTraitHelper()->callGetVersionData($element, $version));
    }

    /**
     * @throws ReflectionException
     */
    public function testGetVersionDataFallsBackToElementWhenVersionDataCannotBeLoaded(): void
    {
        $element = $this->createMock(Concrete::class);
        // the storage adapter returns no data, e.g. because the version file is missing
        $version = $this->createVersion(null);

        $this->assertSame($element, $this->createTraitHelper()->callGetVersionData($element, $version));
    }

    /**
     * @throws ReflectionException
     */
    private function createVersion(mixed $storedData): Version
    {
        $storageAdapter = $this->createMock(VersionStorageAdapterInterface::class);
        $storageAdapter->method('loadMetaData')->willReturn($storedData);

        // Version's constructor pulls the storage adapter from the container
        $reflection = new ReflectionClass(Version::class);
        /** @var Version $version */
        $version = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('storageAdapter')->setValue($version, $storageAdapter);

        return $version;
    }

    private function createTraitHelper(): object
    {
        return new class() {
            use ElementProviderTrait;

            public function callGetVersionData(ElementInterface $element, ?Version $version): ElementInterface
            {
                return $this->getVersionData($element, $version);
            }
        };
    }
}
