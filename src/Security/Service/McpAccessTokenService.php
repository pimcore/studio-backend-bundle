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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Service;

use Closure;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Mcp\McpAccessToken;
use Pimcore\Bundle\StudioBackendBundle\Security\Dto\ValidatedAccessToken;
use Pimcore\Bundle\StudioBackendBundle\Security\Exception\McpTokenUserInvalidException;
use Pimcore\Bundle\StudioBackendBundle\Security\Repository\McpAccessTokenRepositoryInterface;
use Pimcore\Model\User;
use function bin2hex;
use function hash;
use function random_bytes;
use function str_starts_with;
use function time;

/**
 * @internal
 */
final readonly class McpAccessTokenService implements McpAccessTokenServiceInterface
{
    public const string TOKEN_PREFIX = 'pmcp_';

    /** @var Closure(int): ?User */
    private Closure $userLoader;

    /**
     * @param Closure(int): ?User|null $userLoader injection seam for tests; defaults to User::getById
     */
    public function __construct(
        private McpAccessTokenRepositoryInterface $repository,
        private AuthenticationResolverInterface $authenticationResolver,
        ?Closure $userLoader = null,
    ) {
        $this->userLoader = $userLoader ?? static fn (int $id): ?User => User::getById($id);
    }

    public function issue(int $userId, int $ttlSeconds, string $reference): string
    {
        if (!$this->isUserValid($userId)) {
            throw new McpTokenUserInvalidException(
                'Cannot issue MCP token: user ' . $userId . ' is missing or disabled.'
            );
        }

        // Enforce exactly-one-live-token-per-reference: if a prior token row exists
        // for the same chat session (e.g. a re-mint after a failed refresh, or a
        // duplicate issue), wipe it so the old plaintext value can no longer be used.
        $this->repository->deleteByReference($reference);

        $token = self::TOKEN_PREFIX . bin2hex(random_bytes(32));
        $now = time();
        $this->repository->save(new McpAccessToken(
            hash('sha256', $token),
            $userId,
            $reference,
            $now + $ttlSeconds,
            $now,
        ));

        return $token;
    }

    public function refresh(string $reference, int $ttlSeconds): bool
    {
        $token = $this->repository->findOneByReference($reference);
        if ($token === null || $token->getExpiresAt() < time()) {
            return false;
        }

        if (!$this->isUserValid($token->getUserId())) {
            $this->repository->deleteByReference($reference);

            return false;
        }

        $token->setExpiresAt(time() + $ttlSeconds);
        $this->repository->save($token);

        return true;
    }

    public function revoke(string $reference): void
    {
        $this->repository->deleteByReference($reference);
    }

    public function revokeByUser(int $userId): void
    {
        $this->repository->deleteByUserId($userId);
    }

    public function validate(string $token): ?ValidatedAccessToken
    {
        if (!str_starts_with($token, self::TOKEN_PREFIX)) {
            return null;
        }

        $row = $this->repository->findByHash(hash('sha256', $token));
        if ($row === null || $row->getExpiresAt() < time()) {
            return null;
        }

        $user = ($this->userLoader)($row->getUserId());
        if (!$this->authenticationResolver->isValidUser($user)) {
            return null;
        }

        return new ValidatedAccessToken($user, $row->getReference());
    }

    private function isUserValid(int $userId): bool
    {
        return $this->authenticationResolver->isValidUser(($this->userLoader)($userId));
    }
}
