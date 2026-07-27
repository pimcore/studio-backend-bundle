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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Cache\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Cache\Service\CacheClearerService;
use Pimcore\Cache\Core\CoreCacheHandler;
use Pimcore\Cache\Symfony\CacheClearer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

/**
 * Regression coverage for issue #1879: clearing the Symfony cache from the Studio UI must not
 * run "bin/console cache:clear" synchronously during the active request. That subprocess deletes
 * and rebuilds the DI container directory the running PHP process has already loaded, so any
 * subsequent lazy service require() crashes fatally. The clear must be deferred to
 * kernel.terminate (after the response is sent) and must run after all other terminate listeners.
 */
final class CacheClearerServiceTest extends Unit
{
    public function testClearSymfonyCacheDoesNotClearDuringTheRequest(): void
    {
        $cleared = [];
        $eventDispatcher = new EventDispatcher();
        $service = $this->createService($eventDispatcher, $this->createRecordingCacheClearer($cleared));

        $service->clearSymfonyCache(['prod']);

        // The regression: nothing may be cleared while the request is still being handled.
        $this->assertSame([], $cleared);
    }

    public function testClearSymfonyCacheClearsOnKernelTerminate(): void
    {
        $cleared = [];
        $eventDispatcher = new EventDispatcher();
        $service = $this->createService($eventDispatcher, $this->createRecordingCacheClearer($cleared));

        $service->clearSymfonyCache(['prod']);
        $eventDispatcher->dispatch(new GenericEvent(), KernelEvents::TERMINATE);

        $this->assertSame(['prod'], $cleared);
    }

    public function testClearSymfonyCacheDefaultsToKernelEnvironment(): void
    {
        $cleared = [];
        $eventDispatcher = new EventDispatcher();
        $service = $this->createService(
            $eventDispatcher,
            $this->createRecordingCacheClearer($cleared),
            'staging'
        );

        $service->clearSymfonyCache();
        $eventDispatcher->dispatch(new GenericEvent(), KernelEvents::TERMINATE);

        $this->assertSame(['staging'], $cleared);
    }

    public function testClearSymfonyCacheClearsEveryGivenEnvironment(): void
    {
        $cleared = [];
        $eventDispatcher = new EventDispatcher();
        $service = $this->createService($eventDispatcher, $this->createRecordingCacheClearer($cleared));

        $service->clearSymfonyCache(['dev', 'prod']);
        $eventDispatcher->dispatch(new GenericEvent(), KernelEvents::TERMINATE);

        $this->assertSame(['dev', 'prod'], $cleared);
    }

    public function testSymfonyCacheClearRunsAfterOtherTerminateListeners(): void
    {
        $order = [];
        $eventDispatcher = new EventDispatcher();

        // Simulate another deferred listener that still relies on the container (e.g. the
        // Pimcore cache clear registered by clearPimcoreCache()). It must run first.
        $eventDispatcher->addListener(KernelEvents::TERMINATE, static function () use (&$order): void {
            $order[] = 'other';
        });

        $process = $this->makeEmpty(Process::class);
        $cacheClearer = $this->makeEmpty(CacheClearer::class, [
            'clear' => static function () use (&$order, $process): Process {
                $order[] = 'symfony';

                return $process;
            },
        ]);
        $service = $this->createService($eventDispatcher, $cacheClearer);

        $service->clearSymfonyCache(['prod']);
        $eventDispatcher->dispatch(new GenericEvent(), KernelEvents::TERMINATE);

        $this->assertSame(['other', 'symfony'], $order);
    }

    /**
     * @param array<int, string> $cleared captured by reference; each cleared environment is appended
     */
    private function createRecordingCacheClearer(array &$cleared): CacheClearer
    {
        $process = $this->makeEmpty(Process::class);

        return $this->makeEmpty(CacheClearer::class, [
            'clear' => static function (string $environment) use (&$cleared, $process): Process {
                $cleared[] = $environment;

                return $process;
            },
        ]);
    }

    private function createService(
        EventDispatcherInterface $eventDispatcher,
        CacheClearer $cacheClearer,
        string $environment = 'prod',
    ): CacheClearerService {
        return new CacheClearerService(
            $this->makeEmpty(CoreCacheHandler::class),
            $this->makeEmpty(Filesystem::class),
            $eventDispatcher,
            $cacheClearer,
            $this->makeEmpty(KernelInterface::class, [
                'getEnvironment' => $environment,
            ]),
        );
    }
}
