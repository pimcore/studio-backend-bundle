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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Controller\CustomLayout;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout\ClassCollectionController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout\CreateController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout\DeleteController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout\EditorCollectionController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout\ExportController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout\GetController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout\GetIdentifierController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout\ImportController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout\UpdateController;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function sprintf;

/**
 * Regression test for GHSA-5r9r-72j4-gv74.
 *
 * The CustomLayout CRUD/import/export/get routes must require the CLASS_DEFINITION
 * ('classes') permission, matching ClassDefinitionType::CustomLayout->permission(),
 * not DATA_OBJECTS ('objects') as they did before the fix.
 *
 * The editor collection route is the exception: the data object editor calls it whenever an
 * object is opened. It is read-only, and the service enforces view permission on the object,
 * so it requires DATA_OBJECTS ('objects') (pimcore/platform-version#597).
 *
 * @internal
 */
final class CustomLayoutControllerPermissionTest extends Unit
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function controllerProvider(): iterable
    {
        yield CreateController::class => [CreateController::class];
        yield UpdateController::class => [UpdateController::class];
        yield DeleteController::class => [DeleteController::class];
        yield ImportController::class => [ImportController::class];
        yield ExportController::class => [ExportController::class];
        yield GetController::class => [GetController::class];
        yield GetIdentifierController::class => [GetIdentifierController::class];
        yield ClassCollectionController::class => [ClassCollectionController::class];
    }

    /**
     * @dataProvider controllerProvider
     *
     * @param class-string $controllerClass
     *
     * @throws ReflectionException
     */
    public function testInvokeRequiresClassDefinitionPermission(string $controllerClass): void
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
            UserPermissions::CLASS_DEFINITION->value,
            $isGranted->attribute,
            sprintf(
                '%s::%s() must require the "%s" permission (GHSA-5r9r-72j4-gv74), not "%s".',
                $controllerClass,
                $actionMethod->getName(),
                UserPermissions::CLASS_DEFINITION->value,
                (string) $isGranted->attribute
            )
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testEditorCollectionRequiresDataObjectsPermission(): void
    {
        $reflection = new ReflectionClass(EditorCollectionController::class);
        $attributes = $this->resolveActionMethod($reflection)->getAttributes(IsGranted::class);

        $this->assertNotEmpty($attributes, 'EditorCollectionController is missing an #[IsGranted] attribute.');
        $this->assertSame(
            UserPermissions::DATA_OBJECTS->value,
            $attributes[0]->newInstance()->attribute,
            'The editor collection route must be available to every user who can open data objects.'
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
