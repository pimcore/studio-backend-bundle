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

namespace Pimcore\Bundle\StudioBackendBundle\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StudioBackendPathTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @internal
 */
final readonly class SessionCloseSubscriber implements EventSubscriberInterface
{
    use StudioBackendPathTrait;

    public function __construct(
        private string $urlPrefix,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->isStudioBackendPath($request->getPathInfo(), $this->urlPrefix)) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}
