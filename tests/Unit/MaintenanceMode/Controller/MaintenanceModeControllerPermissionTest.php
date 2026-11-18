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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\MaintenanceMode\Controller;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Controller\ActivateController;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Controller\DeactivateController;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Controller\GetController;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function sprintf;

/**
 * Maintenance mode is admin-only, as in the classic admin UI, which shows it to admins only.
 *
 * @internal
 */
final class MaintenanceModeControllerPermissionTest extends Unit
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function controllerProvider(): iterable
    {
        yield GetController::class => [GetController::class];
        yield ActivateController::class => [ActivateController::class];
        yield DeactivateController::class => [DeactivateController::class];
    }

    /**
     * @dataProvider controllerProvider
     *
     * @param class-string $controllerClass
     *
     * @throws ReflectionException
     */
    public function testActionRequiresAdmin(string $controllerClass): void
    {
        $actionMethod = $this->resolveActionMethod(new ReflectionClass($controllerClass));
        $attributes = $actionMethod->getAttributes(IsGranted::class);

        $this->assertNotEmpty(
            $attributes,
            sprintf('%s::%s() is missing an #[IsGranted] attribute.', $controllerClass, $actionMethod->getName())
        );
        $this->assertSame(
            UserPermissions::PIMCORE_ADMIN->value,
            $attributes[0]->newInstance()->attribute,
            sprintf('%s::%s() must be admin-only.', $controllerClass, $actionMethod->getName())
        );
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function resolveActionMethod(ReflectionClass $reflection): ReflectionMethod
    {
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isConstructor() && $method->getAttributes(Route::class) !== []) {
                return $method;
            }
        }

        throw new RuntimeException(
            sprintf('%s has no public method with a #[Route] attribute.', $reflection->getName())
        );
    }
}
