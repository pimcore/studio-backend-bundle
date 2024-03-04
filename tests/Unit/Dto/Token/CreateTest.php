<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Tests\Unit\Dto\Token;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Create;

final class CreateTest extends Unit
{
    public function testTokenCreate(): void
    {
        $create = new Create('token', 'test');
        $this->assertSame('token', $create->getUsername());
        $this->assertSame('test', $create->getPassword());
    }
}