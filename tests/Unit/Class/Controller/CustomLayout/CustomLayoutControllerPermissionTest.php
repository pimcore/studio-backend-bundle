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
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Regression test for GHSA-5r9r-72j4-gv74.
 *
 * The CustomLayout CRUD/import/export/get routes must require the CLASS_DEFINITION
 * ('classes') permission, matching ClassDefinitionType::CustomLayout->permission(),
 * not DATA_OBJECTS ('objects') as they did before the fix.
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
        yield EditorCollectionController::class => [EditorCollectionController::class];
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
        $attributes = $reflection->getMethod('__invoke')->getAttributes(IsGranted::class);

        $this->assertNotEmpty(
            $attributes,
            sprintf('%s::__invoke() is missing an #[IsGranted] attribute.', $controllerClass)
        );

        $isGranted = $attributes[0]->newInstance();

        $this->assertSame(
            UserPermissions::CLASS_DEFINITION->value,
            $isGranted->attribute,
            sprintf(
                '%s::__invoke() must require the "%s" permission (GHSA-5r9r-72j4-gv74), not "%s".',
                $controllerClass,
                UserPermissions::CLASS_DEFINITION->value,
                (string) $isGranted->attribute
            )
        );
    }
}
