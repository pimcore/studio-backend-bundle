<?php

namespace Pimcore\Bundle\StudioApiBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;


final class ApiExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // the priority must be greater than the Security HTTP
            // ExceptionListener, to make sure it's called before
            // the default exception listener
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // ... perform some action (e.g. logging)

        // optionally set the custom response
        $event->setResponse(new Response($exception->getMessage(),  $exception->getCode()));

        // or stop propagation (prevents the next exception listeners from being called)
        //$event->stopPropagation();
    }
}