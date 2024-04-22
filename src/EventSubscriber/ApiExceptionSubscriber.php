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

namespace Pimcore\Bundle\StudioApiBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * @internal
 */
final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    use StudioApiPathTrait;

    public function __construct(private readonly string $environment)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.exception' => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if(!$this->isStudioApiPath($request->getPathInfo())) {
            return;
        }

        if(!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $event->setResponse($this->createResponse($exception));
    }

    private function createResponse(HttpExceptionInterface $exception): Response
    {
        if(!$exception->getMessage()) {
            return new Response(null, $exception->getStatusCode());
        }
        $responseData = [
            'message' => $exception->getMessage(),
        ];

        if ($this->environment === 'dev') {
            $responseData['detail'] = $exception->getTraceAsString();
        }

        return new JsonResponse(
            $responseData,
            $exception->getStatusCode(),
        );
    }
}
