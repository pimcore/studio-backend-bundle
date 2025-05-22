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

use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
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
        private SecurityServiceInterface $securityService,
        private ToolResolverInterface $toolResolver,
    ) {
    }

    public function getUserAllowedLanguages(
        DataObject $dataObject,
        UserInterface $user,
        string $permission
    ): array {
        if (!in_array($permission, ElementPermissions::LANGUAGE_PERMISSIONS)) {
            throw new InvalidArgumentException(sprintf('Invalid permission "%s"', $permission));
        }

        $languagePermissions =  $this->securityService->getSpecialDataObjectPermissions(
            $dataObject,
            $user,
            $permission
        );

        if (empty($languagePermissions) || $this->isDefaultLanguagePermission($languagePermissions)) {
            return $this->toolResolver->getValidLanguages();
        }

        return $languagePermissions;
    }

    private function isDefaultLanguagePermission(array $permissions): bool
    {
        return count($permissions) === 1 && in_array($permissions[0], ['', 'default'], true);
    }
}
