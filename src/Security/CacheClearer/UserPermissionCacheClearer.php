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

namespace Pimcore\Bundle\StudioBackendBundle\Security\CacheClearer;

use Pimcore\Bundle\StaticResolverBundle\Lib\CacheResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CacheKeys;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

/**
 * Drops the cached list of permission definitions on cache:clear, which also runs after
 * pimcore:bundle:install, so permissions created by a bundle installer are picked up
 * by the UserPermissionVoter.
 *
 * @internal
 */
final readonly class UserPermissionCacheClearer implements CacheClearerInterface
{
    public function __construct(
        private CacheResolverInterface $cacheResolver
    ) {
    }

    public function clear(string $cacheDir): void
    {
        $this->cacheResolver->remove(CacheKeys::USER_PERMISSIONS->value);
    }
}
