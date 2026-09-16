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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Identity;

use Closure;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\IdentityResolverInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function ctype_digit;

/**
 * Embedded-AS identity resolver: the token subject is the numeric Pimcore user
 * id. Resolution mirrors the inline check in
 * {@see \Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenService::validate()}
 * — there is deliberately no run-as / effective-user indirection in this
 * bundle. A disabled, deleted or otherwise invalid user resolves to null.
 *
 * @internal
 */
final readonly class EmbeddedIdentityResolver implements IdentityResolverInterface
{
    /** @var Closure(int): ?User */
    private Closure $userLoader;

    /**
     * @param Closure(int): ?User|null $userLoader injection seam for tests; defaults to User::getById
     */
    public function __construct(
        private AuthenticationResolverInterface $authenticationResolver,
        ?Closure $userLoader = null,
    ) {
        $this->userLoader = $userLoader ?? static fn (int $id): ?User => User::getById($id);
    }

    public function resolve(string $subject): ?UserInterface
    {
        // Subject is the numeric Pimcore user id.
        if (!ctype_digit($subject)) {
            return null;
        }

        $user = ($this->userLoader)((int) $subject);
        if (!$this->authenticationResolver->isValidUser($user)) {
            return null;
        }

        return $user;
    }
}
