<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\Util\Traits\StudioBackendPathTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final class CorsSubscriber implements EventSubscriberInterface
{
    use StudioBackendPathTrait;

    private array $routeMethods;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly array $allowedHosts = []
    ) {
        foreach($this->router->getRouteCollection()->getIterator() as $route) {
            if($this->isStudioBackendPath($route->getPath())) {
                $this->routeMethods[$route->getPath()] = implode(', ', $route->getMethods());
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9999],
            KernelEvents::RESPONSE => ['onKernelResponse', 9999],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Only check main requests
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if(!$this->isStudioBackendPath($request->getPathInfo())) {
            return;
        }

        // perform preflight checks
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();

            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Methods', $this->routeMethods[$request->getPathInfo()]);
            $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization');
            $response->headers->set('Access-Control-Max-Age', '3600');

            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isStudioBackendPath($request->getPathInfo())) {
            return;
        }
        // Run CORS check in here to ensure domain is in the system
        $corsOrigin = $request->headers->get('origin');

        if (in_array($corsOrigin, $this->allowedHosts, true)) {
            $response = $event->getResponse();

            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization');
            $response->headers->set('Access-Control-Allow-Origin', $corsOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
            $response->headers->set('Vary', 'Origin');

        }
    }
}
