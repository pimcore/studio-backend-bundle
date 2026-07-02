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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Version\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Version\Hydrator\VersionHydrator;
use Pimcore\Model\Version as PimcoreVersion;
use ReflectionClass;

/**
 * @internal
 */
final class VersionHydratorTest extends Unit
{
    public function testHydrateMapsCoauthorFields(): void
    {
        $version = $this->createCoreVersion();
        $version->setCoauthorType('agent');
        $version->setCoauthor('product-data-agent');

        $result = (new VersionHydrator())->hydrate($version, [], 3, 1712823182);

        $this->assertSame('agent', $result->getCoauthorType());
        $this->assertSame('product-data-agent', $result->getCoauthor());
    }

    public function testHydrateDefaultsCoauthorFieldsToNull(): void
    {
        $result = (new VersionHydrator())->hydrate($this->createCoreVersion(), [], 3, 1712823182);

        $this->assertNull($result->getCoauthorType());
        $this->assertNull($result->getCoauthor());
    }

    private function createCoreVersion(): PimcoreVersion
    {
        /** @var PimcoreVersion $version */
        $version = (new ReflectionClass(PimcoreVersion::class))->newInstanceWithoutConstructor();
        $version->setId(2);
        $version->setCid(10);
        $version->setCtype('object');
        $version->setNote('some note');
        $version->setDate(1712823182);
        $version->setVersionCount(3);

        return $version;
    }
}
