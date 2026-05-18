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

namespace Pimcore\Bundle\StudioBackendBundle\Cache\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\CacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\StorageResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Cache\MappedParameter\ClearCacheParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Event\SystemEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use function recursiveDelete;

/**
 * @internal
 */
final readonly class CacheService implements CacheServiceInterface
{
    public function __construct(
        private CacheClearerServiceInterface $cacheClearerService,
        private CacheResolverInterface $cacheResolver,
        private StorageResolverInterface $storageResolver,
        private DbResolverInterface $dbResolver,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function clearCache(ClearCacheParameters $parameters): void
    {
        $clearPimcoreCache = !$parameters->getOnlySymfonyCache();
        $clearSymfonyCache = !$parameters->getOnlyPimcoreCache();

        if ($clearPimcoreCache) {
            $this->cacheClearerService->clearPimcoreCache();
        }

        if ($clearSymfonyCache) {
            $this->cacheClearerService->clearSymfonyCache();
        }
    }

    public function clearOutputCache(): void
    {
        try {
            $this->cacheResolver->removeIgnoredTagOnClear('output');
            $this->cacheResolver->clearTags(['output', 'output_lifetime']);
        } catch (Exception $e) {
            throw new EnvironmentException($e->getMessage());
        }

        $this->eventDispatcher->dispatch(new GenericEvent(), SystemEvents::CACHE_CLEAR_FULLPAGE_CACHE);
    }

    public function clearTemporaryFiles(): void
    {
        try {
            $this->storageResolver->get('thumbnail')->deleteDirectory('/');
            $this->dbResolver->get()->executeQuery('TRUNCATE TABLE assets_image_thumbnail_cache');
            $this->storageResolver->get('asset_cache')->deleteDirectory('/');

            recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY, false);
        } catch (Exception $e) {
            throw new EnvironmentException($e->getMessage());
        }

        $this->eventDispatcher->dispatch(new GenericEvent(), SystemEvents::CACHE_CLEAR_TEMPORARY_FILES);
    }
}
