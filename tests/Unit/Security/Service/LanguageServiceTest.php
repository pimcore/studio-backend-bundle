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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
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
    public function testAdminsGetTheLanguageIndependentValueAndEveryValidLanguage(): void
    {
        $this->assertSame(
            ['default', 'de', 'en'],
            $this->resolveLanguages(['de'], isAdmin: true)
        );
    }

    public function testWithoutAnyLanguageRestrictionEveryLanguageIsAllowed(): void
    {
        $this->assertSame(
            ['default', 'de', 'en'],
            $this->resolveLanguages([])
        );
    }

    public function testAnEmptyPermissionStringIsTreatedAsNoRestriction(): void
    {
        $this->assertSame(
            ['default', 'de', 'en'],
            $this->resolveLanguages([''])
        );
    }

    /**
     * Unlike for localized fields, the language independent value is a real column of a localized
     * Classification Store. Granting only that column must therefore not hand out any language.
     */
    public function testGrantingOnlyTheLanguageIndependentValueGrantsNoLanguage(): void
    {
        $this->assertSame(
            ['default'],
            $this->resolveLanguages(['default'])
        );
    }

    public function testTheLanguageIndependentValueIsPrependedWhenGrantedNextToLanguages(): void
    {
        $this->assertSame(
            ['default', 'de'],
            $this->resolveLanguages(['de', 'default'], ElementPermissions::LANGUAGE_EDIT_PERMISSIONS)
        );
    }

    public function testTheLanguageIndependentValueIsDeniedWhenLeftOutOfTheLanguageList(): void
    {
        $this->assertSame(
            ['de', 'en'],
            $this->resolveLanguages(['de', 'en'], ElementPermissions::LANGUAGE_EDIT_PERMISSIONS)
        );
    }

    public function testAnUnknownPermissionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resolveLanguages(['de'], 'view');
    }

    public function testWebsiteTranslationLanguagesAreLimitedToValidLanguages(): void
    {
        $service = $this->createTranslationLanguageService(['de', 'en', 'fr_BE']);
        $user = $this->makeEmpty(UserInterface::class, [
            'getAllowedLanguagesForViewingWebsiteTranslations' => ['fr_BE', 'xx_unknown', 'de'],
        ]);

        $this->assertSame(['fr_BE', 'de'], $service->getTranslationAllowedLanguages($user, 'messages'));
    }

    public function testWebsiteTranslationsAreForbiddenWithoutAnyValidLanguage(): void
    {
        $service = $this->createTranslationLanguageService(['de', 'en']);
        $user = $this->makeEmpty(UserInterface::class, [
            'getAllowedLanguagesForViewingWebsiteTranslations' => ['xx_unknown'],
        ]);

        $this->expectException(ForbiddenException::class);

        $service->getTranslationAllowedLanguages($user, 'messages');
    }

    public function testAdminTranslationLanguagesAreNotLimitedToValidLanguages(): void
    {
        $service = $this->createTranslationLanguageService(['de']);
        $user = $this->makeEmpty(UserInterface::class);

        $this->assertSame(['en', 'cs', 'zh_Hans'], $service->getTranslationAllowedLanguages($user, 'admin'));
        $this->assertSame(['en', 'cs', 'zh_Hans'], $service->getTranslationAllowedLanguages($user, 'studio'));
    }

    /**
     * @param array<int, string> $validLanguages
     */
    private function createTranslationLanguageService(array $validLanguages): LanguageService
    {
        return new LanguageService(
            $this->makeEmpty(AdminLanguageServiceInterface::class, [
                'getAvailableAdminLanguages' => ['en', 'cs', 'zh_Hans'],
            ]),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(ToolResolverInterface::class, [
                'getValidLanguages' => $validLanguages,
            ]),
        );
    }

    /**
     * @param array<int, string> $languagePermissions
     *
     * @return array<int, string>
     */
    private function resolveLanguages(
        array $languagePermissions,
        string $permission = ElementPermissions::LANGUAGE_VIEW_PERMISSIONS,
        bool $isAdmin = false
    ): array {
        $service = new LanguageService(
            $this->makeEmpty(AdminLanguageServiceInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getSpecialDataObjectPermissions' => $languagePermissions,
            ]),
            $this->makeEmpty(ToolResolverInterface::class, [
                'getValidLanguages' => ['de', 'en'],
            ]),
        );

        return $service->getUserAllowedLanguagesWithLanguageIndependentValue(
            $this->makeEmpty(DataObject::class),
            $this->makeEmpty(UserInterface::class, ['isAdmin' => $isAdmin]),
            $permission
        );
    }
}
