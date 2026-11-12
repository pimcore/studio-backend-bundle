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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Email\Controller;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\CacheResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Email\Controller\CollectionController;
use Pimcore\Bundle\StudioBackendBundle\Email\Service\EmailLogServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Voter\HasOneOfUserPermissionVoter;
use Pimcore\Bundle\StudioBackendBundle\Security\Voter\UserPermissionVoter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\User;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Regression test for pimcore/platform-version#359.
 *
 * The sent-emails list must be available to users holding "emails" OR "gdpr_data_extractor",
 * exactly like the legacy admin-ui-classic EmailController::emailLogsAction(). It used to require
 * both, which also locked out admins on installations where "gdpr_data_extractor" is not a
 * defined permission key (it is only registered by the admin-ui-classic installer).
 *
 * @internal
 */
final class CollectionControllerTest extends Unit
{
    /**
     * @return iterable<string, array{array<int, string>, bool}>
     */
    public static function grantedUserProvider(): iterable
    {
        yield 'emails only' => [[UserPermissions::EMAILS->value], false];
        yield 'gdpr only' => [[UserPermissions::GDPR->value], false];
        yield 'emails and gdpr' => [[UserPermissions::EMAILS->value, UserPermissions::GDPR->value], false];
        yield 'admin' => [[], true];
    }

    /**
     * @dataProvider grantedUserProvider
     *
     * @param array<int, string> $permissions
     */
    public function testListIsGrantedWithEmailsOrGdprPermission(array $permissions, bool $isAdmin): void
    {
        $response = $this->callAuthorized($permissions, $isAdmin);

        $this->assertSame('3', $response->headers->get('X-Pimcore-Total-Items'));
    }

    public function testListIsDeniedWithoutEmailsAndGdprPermission(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->callAuthorized([UserPermissions::DOCUMENTS->value], false);
    }

    public function testListIsGrantedToAdminWhenGdprPermissionKeyIsNotDefined(): void
    {
        $response = $this->callAuthorized([], true, [UserPermissions::EMAILS->value]);

        $this->assertSame('3', $response->headers->get('X-Pimcore-Total-Items'));
    }

    /**
     * Emulates Symfony's IsGrantedAttributeListener for every #[IsGranted] on the action and then
     * invokes the action, so both declarative and in-action checks are covered.
     *
     * @param array<int, string> $permissions
     * @param array<int, string> $definedPermissionKeys
     */
    private function callAuthorized(
        array $permissions,
        bool $isAdmin,
        array $definedPermissionKeys = [
            UserPermissions::EMAILS->value,
            UserPermissions::GDPR->value,
            UserPermissions::DOCUMENTS->value,
        ]
    ): JsonResponse {
        $user = (new User())->setAdmin($isAdmin)->setPermissions($permissions);
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $user]);
        $cacheResolver = $this->makeEmpty(CacheResolverInterface::class, ['load' => $definedPermissionKeys]);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new NullToken());
        $authorizationChecker = new AuthorizationChecker(
            $tokenStorage,
            new AccessDecisionManager([
                new UserPermissionVoter(
                    $cacheResolver,
                    $this->makeEmpty(DbResolverInterface::class),
                    $securityService
                ),
                new HasOneOfUserPermissionVoter($securityService),
            ])
        );

        $serializer = $this->makeEmpty(SerializerInterface::class, ['serialize' => '{"items":[]}']);
        $emailLogService = $this->makeEmpty(EmailLogServiceInterface::class, [
            'listEntries' => new Collection(3, []),
        ]);
        $controller = new CollectionController($serializer, $emailLogService);
        $container = new Container();
        $container->set('security.authorization_checker', $authorizationChecker);
        $controller->setContainer($container);

        $method = new ReflectionMethod(CollectionController::class, 'getEmailLogEntries');
        foreach ($method->getAttributes(IsGranted::class) as $attribute) {
            if (!$authorizationChecker->isGranted($attribute->newInstance()->attribute)) {
                throw new AccessDeniedException();
            }
        }

        return $controller->getEmailLogEntries(new CollectionParameters());
    }
}
