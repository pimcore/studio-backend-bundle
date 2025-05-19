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

namespace Pimcore\Bundle\StudioBackendBundle\User\RateLimiter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\RequestTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class RateLimiter implements RateLimiterInterface
{
    use RequestTrait;

    public function __construct(
        private RateLimiterFactory $resetPasswordLimiter,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @throws RateLimitException
     */
    public function check(): void
    {
        $request = $this->getCurrentRequest($this->requestStack);

        $limiter = $this->resetPasswordLimiter->create($request->getClientIp());

        try {
            $limiter->consume()->ensureAccepted();
        } catch (RateLimitExceededException) {
            throw new RateLimitException();
        }

    }
}
