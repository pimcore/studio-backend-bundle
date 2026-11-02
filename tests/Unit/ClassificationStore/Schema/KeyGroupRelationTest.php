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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Schema;

use Codeception\Test\Unit;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyGroupRelation;
use ReflectionClass;
use ReflectionParameter;
use function array_map;

/**
 * @internal
 */
final class KeyGroupRelationTest extends Unit
{
    /**
     * The store-wide key picker answers across groups, where an assignment's sub group has no
     * meaning. A later, well-meant addition of one belongs on the group key list instead.
     */
    public function testThePickerSchemaCarriesNoSubGroup(): void
    {
        $constructor = (new ReflectionClass(KeyGroupRelation::class))->getConstructor();

        $parameters = array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            $constructor?->getParameters() ?? []
        );

        $this->assertSame(
            ['keyId', 'groupId', 'keyName', 'groupName', 'keyDescription', 'groupDescription'],
            $parameters
        );

        $attributes = (new ReflectionClass(KeyGroupRelation::class))->getAttributes(Schema::class);

        $this->assertSame(
            ['keyId', 'groupId', 'keyName', 'groupName', 'keyDescription', 'groupDescription'],
            $attributes[0]->getArguments()['required']
        );
    }
}
