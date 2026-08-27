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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\AdminLanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class LanguageServiceTest extends Unit
{
    public function testLanguageIndependentValueIsAllowedForAdmins(): void
    {
        $service = $this->createService(['de']);

        $this->assertTrue(
            $service->isLanguageIndependentValueAllowed(
                $this->makeEmpty(DataObject::class),
                $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
                ElementPermissions::LANGUAGE_VIEW_PERMISSIONS
            )
        );
    }

    public function testLanguageIndependentValueIsAllowedWithoutAnyLanguageRestriction(): void
    {
        $service = $this->createService([]);

        $this->assertTrue(
            $service->isLanguageIndependentValueAllowed(
                $this->makeEmpty(DataObject::class),
                $this->makeEmpty(UserInterface::class),
                ElementPermissions::LANGUAGE_VIEW_PERMISSIONS
            )
        );
    }

    public function testLanguageIndependentValueIsAllowedForAnEmptyPermissionString(): void
    {
        $service = $this->createService(['']);

        $this->assertTrue(
            $service->isLanguageIndependentValueAllowed(
                $this->makeEmpty(DataObject::class),
                $this->makeEmpty(UserInterface::class),
                ElementPermissions::LANGUAGE_VIEW_PERMISSIONS
            )
        );
    }

    public function testLanguageIndependentValueIsAllowedWhenItIsTheOnlyGrantedPermission(): void
    {
        $service = $this->createService(['default']);

        $this->assertTrue(
            $service->isLanguageIndependentValueAllowed(
                $this->makeEmpty(DataObject::class),
                $this->makeEmpty(UserInterface::class),
                ElementPermissions::LANGUAGE_VIEW_PERMISSIONS
            )
        );
    }

    public function testLanguageIndependentValueIsAllowedWhenGrantedNextToConcreteLanguages(): void
    {
        $service = $this->createService(['default', 'de']);

        $this->assertTrue(
            $service->isLanguageIndependentValueAllowed(
                $this->makeEmpty(DataObject::class),
                $this->makeEmpty(UserInterface::class),
                ElementPermissions::LANGUAGE_EDIT_PERMISSIONS
            )
        );
    }

    public function testLanguageIndependentValueIsDeniedWhenExcludedFromTheLanguageList(): void
    {
        $service = $this->createService(['de', 'en']);

        $this->assertFalse(
            $service->isLanguageIndependentValueAllowed(
                $this->makeEmpty(DataObject::class),
                $this->makeEmpty(UserInterface::class),
                ElementPermissions::LANGUAGE_EDIT_PERMISSIONS
            )
        );
    }

    public function testLanguageIndependentValueRejectsAnUnknownPermission(): void
    {
        $service = $this->createService(['de']);

        $this->expectException(InvalidArgumentException::class);

        $service->isLanguageIndependentValueAllowed(
            $this->makeEmpty(DataObject::class),
            $this->makeEmpty(UserInterface::class),
            'view'
        );
    }

    /**
     * @param array<int, string> $languagePermissions
     */
    private function createService(array $languagePermissions): LanguageService
    {
        return new LanguageService(
            $this->makeEmpty(AdminLanguageServiceInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getSpecialDataObjectPermissions' => $languagePermissions,
            ]),
            $this->makeEmpty(ToolResolverInterface::class, [
                'getValidLanguages' => ['de', 'en'],
            ]),
        );
    }
}
