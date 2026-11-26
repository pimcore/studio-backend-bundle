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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Property\Controller;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Property\Controller\CollectionController;
use Pimcore\Bundle\StudioBackendBundle\Property\Controller\CreateController;
use Pimcore\Bundle\StudioBackendBundle\Property\Controller\DeleteController;
use Pimcore\Bundle\StudioBackendBundle\Property\Controller\UpdateController;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function sprintf;

/**
 * All predefined property routes require the predefined_properties permission.
 *
 * @internal
 */
final class PropertyControllerPermissionTest extends Unit
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function controllerProvider(): iterable
    {
        yield CollectionController::class => [CollectionController::class];
        yield CreateController::class => [CreateController::class];
        yield UpdateController::class => [UpdateController::class];
        yield DeleteController::class => [DeleteController::class];
    }

    /**
     * @dataProvider controllerProvider
     *
     * @param class-string $controllerClass
     *
     * @throws ReflectionException
     */
    public function testRequiresPredefinedPropertiesPermission(string $controllerClass): void
    {
        $reflection = new ReflectionClass($controllerClass);
        $actionMethod = $this->resolveActionMethod($reflection);
        $attributes = $actionMethod->getAttributes(IsGranted::class);

        $this->assertNotEmpty(
            $attributes,
            sprintf('%s::%s() is missing an #[IsGranted] attribute.', $controllerClass, $actionMethod->getName())
        );

        $this->assertSame(
            UserPermissions::PREDEFINED_PROPERTIES->value,
            $attributes[0]->newInstance()->attribute
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
