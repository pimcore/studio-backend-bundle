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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\GdiParsingException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StudioBackendPathTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use function array_key_exists;

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

        // Decoded once, like the router, so an encoded path that reaches a Studio
        // controller is still rendered as a Studio error. See the trait for the detail.
        $path = $this->routedPath($request);

        // RateLimitException wherever it was raised. It is this bundle's own exception and
        // only this bundle throws it, so the path it happened on says nothing useful: what
        // matters is that the client gets the JSON envelope and a 429 rather than Symfony's
        // default error rendering. That covers the MCP endpoints, which are JSON-RPC and own
        // every other error shape they produce, and the OAuth registration endpoint, which
        // sits outside the Studio prefix entirely.
        //
        // Anything else outside the Studio API is left alone: those responses belong to
        // whoever serves that path.
        if (
            !$this->isStudioBackendPath($path, $this->urlPrefix)
            && !$exception instanceof RateLimitException
        ) {
            return;
        }

        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $event->setResponse($this->createResponse($exception));
    }

    private function createResponse(HttpExceptionInterface $exception): Response
    {
        $responseData = $this->getResponseData($exception);

        if (
            $this->environment === 'dev' &&
            array_key_exists('detail', $responseData) === false
        ) {
            $responseData['detail'] = $exception->getTraceAsString();
        }

        return new JsonResponse(
            $responseData,
            $exception->getStatusCode(),
        );
    }

    private function getResponseData(HttpExceptionInterface $exception): array
    {
        if (!$exception instanceof AbstractApiException || !$exception->getMessage()) {
            return [
                'message' => $exception->getMessage(),
            ];
        }

        if ($exception->getPrevious() instanceof ValidationFailedException) {
            return $this->handleSymfonyValidationFailedException(
                $exception->getPrevious(),
                $exception->getMessage()
            );
        }

        if ($exception instanceof GdiParsingException) {
            return $this->handleGdiParsingException($exception);
        }

        return [
            'message' => $exception->getMessage(),
            'errorKey' => $exception->getErrorKey(),
        ];
    }

    private function handleSymfonyValidationFailedException(
        ValidationFailedException $exception,
        string $message
    ): array {
        $violations = $exception->getViolations();
        $collectedViolations = [];

        foreach ($violations as $violation) {
            $collectedViolations[] = [
                'propertyPath' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return [
            'message' => $message,
            'violations' => $collectedViolations,
        ];
    }

    private function handleGdiParsingException(
        GdiParsingException $exception
    ): array {
        return [
            'message' => $exception->getMessage(),
            'position' => $exception->getPosition(),
            'expected' => $exception->getExpected(),
            'query' => $exception->getQuery(),
            'found' => $exception->getFound(),
            'token' => $exception->getToken(),
            'errorKey' => $exception->getErrorKey(),
        ];
    }
}
