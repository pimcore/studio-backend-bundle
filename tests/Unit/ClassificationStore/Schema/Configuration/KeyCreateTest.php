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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyCreate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use ReflectionClass;

/**
 * @internal
 */
final class KeyCreateTest extends Unit
{
    public function testTitleIsOptionalAndNullable(): void
    {
        $this->assertNull((new KeyCreate('weight', 1))->getTitle());
        $this->assertSame('Weight', (new KeyCreate('weight', 1, 'Weight'))->getTitle());

        $attributes = (new ReflectionClass(KeyCreate::class))->getAttributes(Schema::class);

        $this->assertSame(['name', 'storeId'], $attributes[0]->getArguments()['required']);
    }

    public function testEmptyNameIsStillRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new KeyCreate('', 1, 'Weight');
    }
}
