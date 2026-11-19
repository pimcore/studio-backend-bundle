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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Document\Controller\Site;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Document\Controller\Site\DeleteController;
use Pimcore\Bundle\StudioBackendBundle\Document\Controller\Site\UpdateController;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function sprintf;

/**
 * Regression test for GHSA-r8v3-9phw-8m68.
 *
 * The Site update/delete routes create, modify and delete Site configuration (domains, main
 * domain, redirect-to-main-domain). Core gates this with a dedicated 'sites' permission; the
 * routes must require UserPermissions::SITES, not UserPermissions::DOCUMENTS, otherwise any
 * backend user with the ordinary documents permission can rebind production domains.
 *
 * @internal
 */
final class SiteControllerPermissionTest extends Unit
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function controllerProvider(): iterable
    {
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
    public function testRequiresSitesPermission(string $controllerClass): void
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
            UserPermissions::SITES->value,
            $isGranted->attribute,
            sprintf(
                '%s::%s() must require the "%s" permission (GHSA-r8v3-9phw-8m68), not "%s".',
                $controllerClass,
                $actionMethod->getName(),
                UserPermissions::SITES->value,
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
