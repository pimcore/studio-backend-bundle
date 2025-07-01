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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StudioBackendPathTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * @internal
 */
final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    use StudioBackendPathTrait;

    public function __construct(private string $environment, private string $urlPrefix)
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

        if (!$this->isStudioBackendPath($request->getPathInfo(), $this->urlPrefix)) {
            return;
        }

        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $event->setResponse($this->createResponse($exception));
    }

    private function createResponse(HttpExceptionInterface $exception): Response
    {
        if ($exception instanceof HttpException && $exception->getPrevious() instanceof ValidationFailedException) {
            return $this->handleSymfonyValidationFailedException(
                $exception->getPrevious(),
                $exception->getStatusCode(),
                $exception->getMessage()
            );
        }

        if (!$exception instanceof AbstractApiException || !$exception->getMessage()) {
            return new JsonResponse($exception->getMessage(), $exception->getStatusCode());
        }

        $responseData = [
            'message' => $exception->getMessage(),
            'errorKey' => $exception->getErrorKey(),
        ];

        if ($this->environment === 'dev') {
            $responseData['detail'] = $exception->getTraceAsString();
        }

        return new JsonResponse(
            $responseData,
            $exception->getStatusCode(),
        );
    }

    private function handleSymfonyValidationFailedException(
        ValidationFailedException $exception,
        int $status,
        string $message
    ): JsonResponse {
        $violations = $exception->getViolations();
        $collectedViolations = [];

        foreach ($violations as $violation) {
            $collectedViolations[] = [
                'propertyPath' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        $responseData = [
            'message' => $message,
            'violations' => $collectedViolations,
        ];

        if ($this->environment === 'dev') {
            $responseData['detail'] = $exception->getTraceAsString();
        }

        return new JsonResponse(
            $responseData,
            $status
        );
    }
}
