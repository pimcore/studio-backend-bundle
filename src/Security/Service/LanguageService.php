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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\MappingProperty;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\AdminLanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject;
use Pimcore\Model\UserInterface;
use function count;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class LanguageService implements LanguageServiceInterface
{
    public function __construct(
        private AdminLanguageServiceInterface $adminLanguageService,
        private SecurityServiceInterface $securityService,
        private ToolResolverInterface $toolResolver,
    ) {
    }

    public function getUserAllowedLanguages(
        DataObject $dataObject,
        UserInterface $user,
        string $permission
    ): array {
        $this->validateLanguagePermission($permission);

        return $this->resolveAllowedLanguages(
            $this->getLanguagePermissions($dataObject, $user, $permission)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAllowedLanguagesWithLanguageIndependentValue(
        DataObject $dataObject,
        UserInterface $user,
        string $permission
    ): array {
        $this->validateLanguagePermission($permission);

        if ($user->isAdmin()) {
            return array_merge(
                [MappingProperty::NOT_LOCALIZED_KEY],
                $this->toolResolver->getValidLanguages()
            );
        }

        $languagePermissions = $this->getLanguagePermissions($dataObject, $user, $permission);

        $languages = array_values(
            array_filter(
                $this->resolveAllowedLanguages($languagePermissions),
                static fn (string $language): bool => $language !== MappingProperty::NOT_LOCALIZED_KEY
            )
        );

        if (!$this->isLanguageIndependentValueGranted($languagePermissions)) {
            return $languages;
        }

        array_unshift($languages, MappingProperty::NOT_LOCALIZED_KEY);

        return $languages;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAdminPermission(UserInterface $user, string $domain): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($domain === 'admin' && !$user->isAllowed('admin_translations')) {
            throw new ForbiddenException('User does not have permission: admin_translations');
        }
    }

    public function getTranslationAllowedLanguages(UserInterface $user, string $domain): array
    {
        $allowedLanguages = $user->getAllowedLanguagesForViewingWebsiteTranslations();
        if (in_array($domain, [TranslatorServiceInterface::DOMAIN, 'admin'], true)) {
            $allowedLanguages = $this->adminLanguageService->getAvailableAdminLanguages();
        }

        return $allowedLanguages;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateLanguagePermission(string $permission): void
    {
        if (!in_array($permission, ElementPermissions::LANGUAGE_PERMISSIONS, true)) {
            throw new InvalidArgumentException(sprintf('Invalid permission "%s"', $permission));
        }
    }

    /**
     * @return array<int, string>
     */
    private function getLanguagePermissions(
        DataObject $dataObject,
        UserInterface $user,
        string $permission
    ): array {
        return $this->securityService->getSpecialDataObjectPermissions(
            $dataObject,
            $user,
            $permission
        );
    }

    /**
     * @param array<int, string> $languagePermissions
     *
     * @return array<int, string>
     */
    private function resolveAllowedLanguages(array $languagePermissions): array
    {
        if ($languagePermissions === [] || $this->isDefaultLanguagePermission($languagePermissions)) {
            return $this->toolResolver->getValidLanguages();
        }

        return $languagePermissions;
    }

    /**
     * Without an explicit language restriction every language is allowed, the language independent
     * value included. This mirrors the behavior of the classic UI, where the language independent
     * column stays available unless it is left out of the configured language list.
     *
     * @param array<int, string> $languagePermissions
     */
    private function isLanguageIndependentValueGranted(array $languagePermissions): bool
    {
        if ($languagePermissions === [] || $this->isDefaultLanguagePermission($languagePermissions)) {
            return true;
        }

        return in_array(MappingProperty::NOT_LOCALIZED_KEY, $languagePermissions, true);
    }

    private function isDefaultLanguagePermission(array $permissions): bool
    {
        return count($permissions) === 1 && in_array($permissions[0], ['', 'default'], true);
    }
}
