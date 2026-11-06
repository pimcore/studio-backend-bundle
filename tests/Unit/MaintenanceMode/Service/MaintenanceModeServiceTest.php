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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\MaintenanceMode\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Event\PreResponse\MaintenanceModeStateEvent;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Hydrator\MaintenanceModeStateHydrator;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Service\MaintenanceModeService;
use Pimcore\Tool\MaintenanceModeHelperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class MaintenanceModeServiceTest extends Unit
{
    private const string SESSION_ID = 'studio-session-id';

    /**
     * @var string[]
     */
    private array $helperCalls = [];

    /**
     * @var string[]
     */
    private array $dispatched = [];

    private bool $active = false;

    protected function _before(): void
    {
        $this->helperCalls = [];
        $this->dispatched = [];
        $this->active = false;
    }

    /**
     * @throws Exception
     */
    public function testActivatePassesTheRequestSessionIdToTheHelper(): void
    {
        $service = $this->createService(self::SESSION_ID);

        $state = $service->activate();

        $this->assertTrue($state->isActive());
        $this->assertSame(['activate:' . self::SESSION_ID], $this->helperCalls);
        $this->assertSame([MaintenanceModeStateEvent::EVENT_NAME], $this->dispatched);
    }

    /**
     * @throws Exception
     */
    public function testActivateWithoutSessionIdThrowsAndNeverCallsTheHelper(): void
    {
        $service = $this->createService('');

        $this->expectException(EnvironmentException::class);

        try {
            $service->activate();
        } finally {
            $this->assertSame([], $this->helperCalls);
            $this->assertSame([], $this->dispatched);
        }
    }

    /**
     * @throws Exception
     */
    public function testDeactivateMapsTheHelperState(): void
    {
        $this->active = true;
        $service = $this->createService(self::SESSION_ID);

        $state = $service->deactivate();

        $this->assertFalse($state->isActive());
        $this->assertSame(['deactivate'], $this->helperCalls);
        $this->assertSame([MaintenanceModeStateEvent::EVENT_NAME], $this->dispatched);
    }

    /**
     * @throws Exception
     */
    public function testGetStateMapsTheHelperStateWithoutChangingIt(): void
    {
        $this->active = true;
        $service = $this->createService(self::SESSION_ID);

        $state = $service->getState();

        $this->assertTrue($state->isActive());
        $this->assertSame([], $this->helperCalls);
        $this->assertSame([MaintenanceModeStateEvent::EVENT_NAME], $this->dispatched);
    }

    /**
     * @throws Exception
     */
    private function createService(string $sessionId): MaintenanceModeService
    {
        $session = new Session(new MockArraySessionStorage());
        $session->setId($sessionId);
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new MaintenanceModeService(
            $this->makeEmpty(MaintenanceModeHelperInterface::class, [
                'activate' => function (string $sessionId): void {
                    $this->helperCalls[] = 'activate:' . $sessionId;
                    $this->active = true;
                },
                'deactivate' => function (): void {
                    $this->helperCalls[] = 'deactivate';
                    $this->active = false;
                },
                'isActive' => fn (?string $matchSessionId = null): bool => $this->active,
            ]),
            new MaintenanceModeStateHydrator(),
            $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => function (object $event, ?string $name = null): object {
                    $this->dispatched[] = $name;

                    return $event;
                },
            ]),
            $requestStack,
        );
    }
}
