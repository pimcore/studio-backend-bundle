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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Maintenance;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Maintenance\OAuthTokenGcTask;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;

final class OAuthTokenGcTaskTest extends Unit
{
    public function testExecuteDeletesExpiredWithCurrentTime(): void
    {
        $before = time();

        $store = $this->createMock(TokenRecordStoreInterface::class);
        $store->expects($this->once())
            ->method('deleteExpired')
            ->with($this->callback(static fn (int $now): bool => $now >= $before && $now <= time() + 1))
            ->willReturn(3);

        (new OAuthTokenGcTask($store))->execute();
    }
}
