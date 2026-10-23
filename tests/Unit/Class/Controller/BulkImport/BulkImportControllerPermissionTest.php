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
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\BulkImport\DeleteBulkFileController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\BulkImport\ImportController;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\BulkImport\PrepareImportController;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function sprintf;

/**
 * Regression test for GHSA-j8xj-6h6r-chmv.
 *
 * The bulk class-import routes create and overwrite class definitions, field collections,
 * object bricks and custom layouts, the same operation the single-item
 * DefinitionConfiguration Create/Update/Delete/Import controllers perform. Those require the
 * CLASS_DEFINITION ('classes') permission; the bulk path must require the same, not the
 * editor-level DATA_OBJECTS ('objects') permission it used before the fix.
 *
 * @internal
 */
final class BulkImportControllerPermissionTest extends Unit
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function controllerProvider(): iterable
    {
        yield PrepareImportController::class => [PrepareImportController::class];
        yield ImportController::class => [ImportController::class];
        yield DeleteBulkFileController::class => [DeleteBulkFileController::class];
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
                '%s::%s() must require the "%s" permission (GHSA-j8xj-6h6r-chmv), not "%s".',
                $controllerClass,
                $actionMethod->getName(),
                UserPermissions::CLASS_DEFINITION->value,
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
