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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OpenApi\Attribute\Property;

use Codeception\Test\Unit;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\CoauthorProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\CoauthorTypeProperty;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\UpdateVersion;
use ReflectionClass;

final class CoauthorPropertyDeprecationTest extends Unit
{
    public function testPatchAndUpdatePayloadPropertiesAreMarkedDeprecated(): void
    {
        $this->assertTrue((new CoauthorTypeProperty())->deprecated);
        $this->assertTrue((new CoauthorProperty())->deprecated);
    }

    public function testVersionUpdatePropertiesAreMarkedDeprecated(): void
    {
        $constructor = (new ReflectionClass(UpdateVersion::class))->getConstructor();
        $deprecated = [];
        foreach ($constructor->getParameters() as $parameter) {
            foreach ($parameter->getAttributes(Property::class) as $attribute) {
                $deprecated[$parameter->getName()] = $attribute->newInstance()->deprecated;
            }
        }

        $this->assertTrue($deprecated['coauthorType']);
        $this->assertTrue($deprecated['coauthor']);
        $this->assertNotTrue($deprecated['note'] ?? false);
        $this->assertNotTrue($deprecated['public'] ?? false);
    }
}
