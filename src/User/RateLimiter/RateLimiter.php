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

namespace Pimcore\Bundle\StudioBackendBundle\User\RateLimiter;

use Pimcore\Bundle\StudioBackendBundle\Exception\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\RequestTrait;
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
