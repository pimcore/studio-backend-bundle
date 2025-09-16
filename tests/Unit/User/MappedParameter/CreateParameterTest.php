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
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;

/**
 * @internal
 */
final class CreateParameterTest extends TestCase
{
    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter::getName
     */
    public function testGetName(): void
    {
        $parameter = new CreateParameter(1, 'test');
        $this->assertSame('test', $parameter->getName());
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter::getParentId
     */
    public function testGetParentId(): void
    {
        $parameter = new CreateParameter(1, 'test');
        $this->assertSame(1, $parameter->getParentId());
    }
}
