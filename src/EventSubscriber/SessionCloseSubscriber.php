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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @internal
 */
final readonly class SessionCloseSubscriber implements EventSubscriberInterface
{
    use StudioBackendPathTrait;

    public function __construct(
        private string $urlPrefix
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            // Must run on CONTROLLER_ARGUMENTS, not REQUEST: with lazy firewalls the session
            // is only opened when the token is first accessed, which happens in
            // IsGrantedAttributeListener (CONTROLLER_ARGUMENTS, priority 20, or -10000 since
            // Symfony 8.1 where controller attributes are handled after the other listeners).
            // Priority -10500 puts us after it and after the request payload mapping (-10100
            // since Symfony 8.1), so the session is open for both and we can release the lock
            // before the controller runs.
            KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', -10500],
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->isStudioBackendPath($request->getPathInfo(), $this->urlPrefix)) {
            return;
        }

        $this->closeSessionWrite($request->getSession());
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !$this->isStudioBackendPath($request->getPathInfo(), $this->urlPrefix)) {
            return;
        }

        if (!$request->hasSession()) {
            return;
        }

        $this->closeSessionWrite($request->getSession());
    }

    private function closeSessionWrite(SessionInterface $session): void
    {
        if ($session->isStarted()) {
            $session->save();
        }
    }
}
