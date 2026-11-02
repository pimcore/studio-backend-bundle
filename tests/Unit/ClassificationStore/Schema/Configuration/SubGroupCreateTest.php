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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Schema\Configuration;

use Codeception\Test\Unit;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupCreate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use ReflectionClass;

/**
 * @internal
 */
final class SubGroupCreateTest extends Unit
{
    public function testDescriptionIsOptionalAndNullable(): void
    {
        $this->assertNull((new SubGroupCreate('Dimensions', 1))->getDescription());
        $this->assertSame(
            'Measured properties',
            (new SubGroupCreate('Dimensions', 1, 'Measured properties'))->getDescription()
        );

        $attributes = (new ReflectionClass(SubGroupCreate::class))->getAttributes(Schema::class);

        $this->assertSame(['name', 'storeId'], $attributes[0]->getArguments()['required']);
    }

    public function testEmptyNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SubGroupCreate('', 1, 'Measured properties');
    }
}
