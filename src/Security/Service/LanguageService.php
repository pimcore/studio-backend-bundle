<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Security\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\MappingProperty;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject;
use Pimcore\Model\UserInterface;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class LanguageService implements LanguageServiceInterface
{
    public function __construct(
        private SecurityServiceInterface $securityService,
        private ToolResolverInterface $toolResolver
    ) {
    }

    public function getUserAllowedLanguages(
        DataObject $dataObject,
        UserInterface $user,
        string $permission,
        ?array $languages = null
    ): array {
        if (!in_array($permission, ElementPermissions::LANGUAGE_PERMISSIONS)) {
            throw new InvalidArgumentException(sprintf('Invalid permission "%s"', $permission));
        }

        if ($languages === null) {
            $languages = $this->getAllLanguages();
        }

        $userLanguages = $this->securityService->getSpecialDataObjectPermissions(
            $dataObject,
            $user,
            $permission
        );

        return array_intersect($languages, $userLanguages);
    }

    private function getAllLanguages(): array
    {
        $validLanguages = $this->toolResolver->getValidLanguages();

        return array_merge($validLanguages, [MappingProperty::NOT_LOCALIZED_KEY]);
    }
}
