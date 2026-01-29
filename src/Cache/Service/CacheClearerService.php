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

use Pimcore\Cache\Core\CoreCacheHandler;
use Pimcore\Cache\Symfony\CacheClearer;
use Pimcore\Event\SystemEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use function in_array;

/**
 * @internal
 */
final readonly class CacheClearerService implements CacheClearerServiceInterface
{
    public function __construct(
        private CoreCacheHandler $cache,
        private Filesystem $filesystem,
        private EventDispatcherInterface $eventDispatcher,
        private CacheClearer $cacheClearer,
        private KernelInterface $kernel
    ) {
    }

    public function clearPimcoreCache(): void
    {
        $this->eventDispatcher->addListener(KernelEvents::TERMINATE, function () {
            // we need to clear the cache with a delay, because the cache is used by messenger:stop-workers
            // to send the stop signal to all worker processes
            sleep(2);
            $this->clearPimcoreCoreCacheCore();
        });
    }

    private function clearPimcoreCoreCacheCore(): void
    {
        // empty document cache
        $this->cache->clearAll();

        if ($this->filesystem->exists(PIMCORE_CACHE_DIRECTORY)) {
            $this->filesystem->remove(PIMCORE_CACHE_DIRECTORY);
        }

        // PIMCORE-1854 - recreate .dummy file => should remain
        $this->filesystem->dumpFile(PIMCORE_CACHE_DIRECTORY . '/.gitkeep', '');

        $this->eventDispatcher->dispatch(new GenericEvent(), SystemEvents::CACHE_CLEAR);
    }

    public function clearSymfonyCache(
        ?array $environments = null,
    ): void {
        $environments = $environments ?? [$this->kernel->getEnvironment()];

        if (in_array($this->kernel->getEnvironment(), $environments, true)) {
            foreach ($this->eventDispatcher->getListeners(KernelEvents::TERMINATE) as $listener) {
                $this->eventDispatcher->removeListener(KernelEvents::TERMINATE, $listener);
            }

            foreach ($this->eventDispatcher->getListeners(KernelEvents::EXCEPTION) as $listener) {
                $this->eventDispatcher->removeListener(KernelEvents::EXCEPTION, $listener);
            }
        }

        foreach ($environments as $environment) {
            $this->cacheClearer->clear($environment);
        }
    }
}
