<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\MappedParameter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;

/**
 * @internal
 */
final class CreateParameterTest extends Unit
{
    public function testGetName(): void
    {
        $parameter = new CreateParameter(1, 'test');
        $this->assertSame('test', $parameter->getName());
    }

    public function testGetParentId(): void
    {
        $parameter = new CreateParameter(1, 'test');
        $this->assertSame(1, $parameter->getParentId());
    }
}
