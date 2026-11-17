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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Controller\FieldCollection;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\FieldCollection\GetController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\FieldCollection\LayoutController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\FieldCollection\LayoutDefinitionController;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function sprintf;

/**
 * The field collection definition routes must require the FIELD_COLLECTIONS ('fieldcollections')
 * permission, like the other routes of this controller family (TreeController, CreateController, etc.).
 *
 * The object layout route is used by the data object editor, so it requires the DATA_OBJECTS ('objects')
 * permission and the service enforces view permission on the requested object.
 *
 * @internal
 */
final class FieldCollectionControllerPermissionTest extends Unit
{
    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function controllerProvider(): iterable
    {
        yield GetController::class => [GetController::class, UserPermissions::FIELD_COLLECTIONS->value];
        yield LayoutDefinitionController::class => [
            LayoutDefinitionController::class,
            UserPermissions::FIELD_COLLECTIONS->value,
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
