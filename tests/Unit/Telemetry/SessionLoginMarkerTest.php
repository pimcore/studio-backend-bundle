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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Telemetry;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\LoginMarkerInterface;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\SessionLoginMarker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use function time;

/**
 * @internal
 */
final class SessionLoginMarkerTest extends Unit
{
    public function testAFreshMarkerReadsAsAZeroSecondSession(): void
    {
        $request = $this->requestWithSession();
        $marker = new SessionLoginMarker();

        $marker->record($request);

        $duration = $marker->sessionDurationSeconds($request);
        $this->assertNotNull($duration);
        $this->assertGreaterThanOrEqual(0, $duration);
        $this->assertLessThanOrEqual(2, $duration);
    }

    /**
     * The marker is a plain timestamp in the session, so an hour-old login reads as an hour.
     */
    public function testMeasuresFromTheRecordedLoginTime(): void
    {
        $request = $this->requestWithSession();
        $request->getSession()->set(LoginMarkerInterface::SESSION_KEY, time() - 3600);

        $duration = (new SessionLoginMarker())->sessionDurationSeconds($request);

        $this->assertGreaterThanOrEqual(3600, $duration);
        $this->assertLessThanOrEqual(3602, $duration);
    }

    public function testNoMarkerIsUnknownNotZero(): void
    {
        $this->assertNull((new SessionLoginMarker())->sessionDurationSeconds($this->requestWithSession()));
    }

    /**
     * A request without a session (stateless firewall, expired cookie) can neither record nor measure.
     */
    public function testARequestWithoutASessionIsHandledQuietly(): void
    {
        $request = new Request();
        $marker = new SessionLoginMarker();

        $marker->record($request);

        $this->assertNull($marker->sessionDurationSeconds($request));
    }

    /**
     * Whatever else ends up under the key - a string, a timestamp from the future - is not a duration.
     */
    public function testAnUnusableMarkerIsUnknown(): void
    {
        $marker = new SessionLoginMarker();

        $garbage = $this->requestWithSession();
        $garbage->getSession()->set(LoginMarkerInterface::SESSION_KEY, 'yesterday');
        $this->assertNull($marker->sessionDurationSeconds($garbage));

        $future = $this->requestWithSession();
        $future->getSession()->set(LoginMarkerInterface::SESSION_KEY, time() + 600);
        $this->assertNull($marker->sessionDurationSeconds($future));
    }

    private function requestWithSession(): Request
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }
}
