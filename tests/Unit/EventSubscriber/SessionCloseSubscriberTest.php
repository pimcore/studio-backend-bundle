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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\EventSubscriber;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\EventSubscriber\SessionCloseSubscriber;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
final class SessionCloseSubscriberTest extends Unit
{
    /**
     * Symfony 8.1 runs the controller attributes such as #[IsGranted] at priority -10000, and maps the request payloads at -10100.
     */
    public function testClosesTheSessionAfterControllerAttributesAndPayloadMapping(): void
    {
        [, $priority] = SessionCloseSubscriber::getSubscribedEvents()[KernelEvents::CONTROLLER_ARGUMENTS];

        $this->assertLessThan(-10100, $priority);
    }
}
