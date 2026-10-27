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
 * Regression test for GHSA-7xjx-hc6h-48v4.
 *
 * GetController and LayoutDefinitionController must require the FIELD_COLLECTIONS
 * ('fieldcollections') permission, matching their siblings in this controller family
 * (TreeController, CreateController, etc.). Both routes were guarded when introduced but
 * lost their #[IsGranted] attribute without a replacement when the FIELD_COLLECTIONS
 * constant was introduced, leaving them reachable by any authenticated user.
 *
 * @internal
 */
final class FieldCollectionControllerPermissionTest extends Unit
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function controllerProvider(): iterable
    {
        yield GetController::class => [GetController::class];
        yield LayoutDefinitionController::class => [LayoutDefinitionController::class];
    }

    /**
     * @dataProvider controllerProvider
     *
     * @param class-string $controllerClass
     *
     * @throws ReflectionException
     */
    public function testInvokeRequiresFieldCollectionsPermission(string $controllerClass): void
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
            UserPermissions::FIELD_COLLECTIONS->value,
            $isGranted->attribute,
            sprintf(
                '%s::%s() must require the "%s" permission (GHSA-7xjx-hc6h-48v4), not "%s".',
                $controllerClass,
                $actionMethod->getName(),
                UserPermissions::FIELD_COLLECTIONS->value,
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
