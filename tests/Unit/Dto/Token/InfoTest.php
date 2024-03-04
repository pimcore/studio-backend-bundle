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
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Info;

final class InfoTest extends Unit
{
    public function testTokenInfo(): void
    {
        $info = new Info('token', 'test');
        $this->assertSame('token', $info->getToken());
        $this->assertSame('test', $info->getUsername());
    }
}