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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\CacheClearer;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\CacheResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\CacheClearer\UserPermissionCacheClearer;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CacheKeys;

/**
 * @internal
 */
final class UserPermissionCacheClearerTest extends Unit
{
    public function testClearRemovesTheCachedPermissionDefinitions(): void
    {
        $cacheResolver = $this->createMock(CacheResolverInterface::class);
        $cacheResolver->expects($this->once())
            ->method('remove')
            ->with(CacheKeys::USER_PERMISSIONS->value);

        (new UserPermissionCacheClearer($cacheResolver))->clear('/tmp/cache');
    }
}
