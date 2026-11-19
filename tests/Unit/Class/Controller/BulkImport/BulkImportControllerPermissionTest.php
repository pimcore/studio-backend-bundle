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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Controller\BulkImport;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\BulkImport\DeleteBulkFileController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\BulkImport\ImportController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\BulkImport\PrepareImportController;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\BulkImportParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkImport\BulkImportPrepareResponse;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportFileServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportJobServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Util\ClassDefinitionType;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Voter\HasOneOfUserPermissionVoter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Serializer\SerializerInterface;
use function in_array;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;

/**
 * The bulk class-import routes need at least one of the permissions of the importable
 * types (classes, field collections, object bricks); the plain 'objects' permission is not
 * enough. What a user may actually import is enforced per item by the import executor.
 *
 * @internal
 */
final class BulkImportControllerPermissionTest extends Unit
{
    private const ROUTES = ['prepare', 'import', 'delete-file'];

    /**
     * @throws Exception
     */
    public function testRoutesAreDeniedWithoutAnImportPermission(): void
    {
        $deniedPermissionSets = [
            'no permission' => [],
            'objects only' => [UserPermissions::DATA_OBJECTS->value],
            'unrelated permissions' => [UserPermissions::DATA_OBJECTS->value, UserPermissions::DOCUMENTS->value],
        ];

        foreach (self::ROUTES as $route) {
            foreach ($deniedPermissionSets as $label => $permissions) {
                try {
                    $this->callRoute($route, $permissions, false);
                    $this->fail(sprintf('Route "%s" must deny a user with: %s.', $route, $label));
                } catch (ForbiddenException) {
                    $this->addToAssertionCount(1);
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testRoutesAreReachableWithAnyImportPermission(): void
    {
        $allowedPermissionSets = [
            UserPermissions::CLASS_DEFINITION->value,
            UserPermissions::FIELD_COLLECTIONS->value,
            UserPermissions::OBJECT_BRICKS->value,
        ];

        foreach (self::ROUTES as $route) {
            foreach ($allowedPermissionSets as $permission) {
                $this->callRoute($route, [$permission], true);
            }
        }
    }

    public function testImportPermissionsAreThoseOfTheImportableTypes(): void
    {
        $this->assertEqualsCanonicalizing(
            [
                UserPermissions::CLASS_DEFINITION->value,
                UserPermissions::FIELD_COLLECTIONS->value,
                UserPermissions::OBJECT_BRICKS->value,
            ],
            ClassDefinitionType::importPermissions()
        );
        $this->assertNotContains(UserPermissions::DATA_OBJECTS->value, ClassDefinitionType::importPermissions());
    }

    /**
     * @param string[] $permissions
     *
     * @throws Exception
     */
    private function callRoute(string $route, array $permissions, bool $expectServiceCall): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('{}');
        $expectation = $expectServiceCall ? $this->once() : $this->never();

        switch ($route) {
            case 'prepare':
                $service = $this->createMock(BulkImportServiceInterface::class);
                $service->expects($expectation)->method('prepareImport')
                    ->willReturn(new BulkImportPrepareResponse('file-id', []));
                $controller = new PrepareImportController($serializer, $service);
                $file = new UploadedFile((string) tempnam(sys_get_temp_dir(), 'bulk'), 'import.json', null, null, true);
                $request = new Request(files: ['file' => $file]);
                $call = static fn () => $controller->prepareImport($request);

                break;
            case 'import':
                $service = $this->createMock(BulkImportJobServiceInterface::class);
                $service->expects($expectation)->method('importItems')->willReturn(1);
                $controller = new ImportController($serializer, $service);
                $parameters = new BulkImportParameters([['type' => 'class', 'name' => 'Item']]);
                $call = static fn () => $controller->importItems('file-id', $parameters);

                break;
            default:
                $service = $this->createMock(BulkImportFileServiceInterface::class);
                $service->expects($expectation)->method('deleteBulkFile');
                $controller = new DeleteBulkFileController($serializer, $service);
                $call = static fn () => $controller->deleteBulkFile('file-id');
        }

        $this->wireAuthorization($controller, $permissions);
        $call();
    }

    /**
     * @param string[] $permissions
     */
    private function wireAuthorization(AbstractApiController $controller, array $permissions): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('isAllowed')->willReturnCallback(
            static fn (string $permission): bool => in_array($permission, $permissions, true)
        );
        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('getCurrentUser')->willReturn($user);

        $voter = new HasOneOfUserPermissionVoter($securityService);
        $token = $this->createMock(TokenInterface::class);
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturnCallback(
            static fn (mixed $attribute, mixed $subject = null): bool => $voter->vote(
                $token,
                $subject,
                [$attribute]
            ) === VoterInterface::ACCESS_GRANTED
        );

        $container = new Container();
        $container->set('security.authorization_checker', $checker);
        $controller->setContainer($container);
    }
}
