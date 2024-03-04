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
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Output;

final class OutputTest extends Unit
{
    public function testTokenOutput(): void
    {
        $output = new Output('token',3600, 'test');
        $this->assertSame('token', $output->getToken());
        $this->assertSame(3600, $output->getLifetime());
        $this->assertSame('test', $output->getUsername());
    }
}