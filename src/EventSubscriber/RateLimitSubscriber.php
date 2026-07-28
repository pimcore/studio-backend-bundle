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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StudioBackendPathTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * @internal
 */
final class RateLimitSubscriber implements EventSubscriberInterface
{
    use StudioBackendPathTrait;

    private const string RATE_LIMIT_ATTRIBUTE = '_studio_rate_limit';

    private const string MCP_PATH_PREFIX = '/pimcore-mcp/';

    public function __construct(
        private readonly string $urlPrefix,
        private readonly RateLimiterFactory $studioApiGeneralLimiter,
        private readonly bool $enabled = true,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 200],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    /**
     * @throws RateLimitException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (
            $request->getMethod() === 'OPTIONS' ||
            !$this->isRateLimitedPath($request->getPathInfo())
        ) {
            return;
        }

        $key = $request->getClientIp() ?? 'unknown';
        $limiter = $this->studioApiGeneralLimiter->create($key);
        $rateLimit = $limiter->consume();

        $request->attributes->set(self::RATE_LIMIT_ATTRIBUTE, $rateLimit);

        if (!$rateLimit->isAccepted()) {
            throw new RateLimitException();
        }
    }

    /**
     * The MCP firewall (see PimcoreStudioBackendExtension::MCP_FIREWALL_PATTERN) serves a
     * separate path space from the Studio API url_prefix, so it needs listing explicitly
     * or every MCP endpoint escapes the general limiter.
     */
    private function isRateLimitedPath(string $path): bool
    {
        return $this->isStudioBackendPath($path, $this->urlPrefix)
            || str_starts_with($path, self::MCP_PATH_PREFIX);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $rateLimit = $request->attributes->get(self::RATE_LIMIT_ATTRIBUTE);

        if (!$rateLimit instanceof RateLimit) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('X-RateLimit-Limit', (string) $rateLimit->getLimit());
        $response->headers->set(
            'X-RateLimit-Remaining',
            (string) $rateLimit->getRemainingTokens()
        );
        $response->headers->set(
            'X-RateLimit-Reset',
            (string) $rateLimit->getRetryAfter()->getTimestamp()
        );
    }
}
