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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Security\Exception\McpTokenUserInvalidException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Repository\ServiceUserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserMcpToken;
use function bin2hex;
use function random_bytes;
use function sprintf;
use function time;

/**
 * @internal
 */
final readonly class McpTokenService implements McpTokenServiceInterface
{
    private const int SECONDS_PER_DAY = 86400;

    private const string REFERENCE_PREFIX = 'service-user:';

    public function __construct(
        private ServiceUserRepositoryInterface $repository,
        private McpAccessTokenServiceInterface $mcpAccessTokenService,
    ) {
    }

    public function issueToken(int $serviceUserId, int $ttlDays): ServiceUserMcpToken
    {
        if ($ttlDays < 1 || $ttlDays > self::MAX_TTL_DAYS) {
            throw new InvalidArgumentException(
                sprintf('The token lifetime must be between 1 and %d days.', self::MAX_TTL_DAYS)
            );
        }

        $serviceUser = $this->repository->getServiceUser($serviceUserId);
        if (!$serviceUser->isActive()) {
            throw new InvalidArgumentException('An inactive service user gets no token.');
        }

        $ttlSeconds = $ttlDays * self::SECONDS_PER_DAY;
        // A reference of its own per token: issuing replaces the token with the same reference.
        $reference = self::REFERENCE_PREFIX . $serviceUserId . ':' . bin2hex(random_bytes(8));
        $expiresAt = time() + $ttlSeconds;

        try {
            $token = $this->mcpAccessTokenService->issue($serviceUserId, $ttlSeconds, $reference);
        } catch (McpTokenUserInvalidException $exception) {
            throw new InvalidArgumentException('The service user cannot get a token.', $exception);
        }

        return new ServiceUserMcpToken($token, $expiresAt);
    }

    public function revokeTokens(int $serviceUserId): void
    {
        $this->repository->getServiceUser($serviceUserId);
        $this->mcpAccessTokenService->revokeByUser($serviceUserId);
    }
}
