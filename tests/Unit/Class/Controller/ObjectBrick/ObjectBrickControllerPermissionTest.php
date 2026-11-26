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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Controller\ObjectBrick;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\ObjectBrick\CollectionController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\ObjectBrick\GetController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\ObjectBrick\LayoutController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\ObjectBrick\LayoutDefinitionController;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function sprintf;

/**
 * The object brick read routes must require a permission, like the rest of this controller family.
 *
 * @internal
 */
final class ObjectBrickControllerPermissionTest extends Unit
{
    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function controllerProvider(): iterable
    {
        yield CollectionController::class => [CollectionController::class, UserPermissions::CLASS_DEFINITION->value];
        yield GetController::class => [GetController::class, UserPermissions::OBJECT_BRICKS->value];
        yield LayoutDefinitionController::class => [
            LayoutDefinitionController::class,
            UserPermissions::OBJECT_BRICKS->value,
        ];
        yield LayoutController::class => [LayoutController::class, UserPermissions::DATA_OBJECTS->value];
    }

    /**
     * @dataProvider controllerProvider
     *
     * @param class-string $controllerClass
     *
     * @throws ReflectionException
     */
    public function testInvokeRequiresPermission(string $controllerClass, string $expectedPermission): void
    {
        $reflection = new ReflectionClass($controllerClass);
        $actionMethod = $this->resolveActionMethod($reflection);
        $attributes = $actionMethod->getAttributes(IsGranted::class);

        $this->assertNotEmpty(
            $attributes,
            sprintf('%s::%s() is missing an #[IsGranted] attribute.', $controllerClass, $actionMethod->getName())
        );

        $isGranted = $attributes[0]->newInstance();

        $this->assertSame(
            $expectedPermission,
            $isGranted->attribute,
            sprintf(
                '%s::%s() must require the "%s" permission, not "%s".',
                $controllerClass,
                $actionMethod->getName(),
                $expectedPermission,
                (string) $isGranted->attribute
            )
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
