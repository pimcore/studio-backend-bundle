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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\MappedParameter;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UserCloneParameter;

/**
 * @internal
 */
final class UserCloneParameterTest extends TestCase
{
    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UserCloneParameter::getName
     */
    public function testGetName(): void
    {
        $parameter = new UserCloneParameter('test');

        self::assertSame('test', $parameter->getName());
    }
}
