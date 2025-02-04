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

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function explode;
use function preg_match;
use function sprintf;

/**
 * @internal
 */
final readonly class LayoutService implements LayoutServiceInterface
{
    public function getUserAllowedLayoutsByClass(DataObject $dataObject, UserInterface $user): array
    {
        $allowedLayouts = $this->getUserAllowedLayouts($dataObject, $user);

        $userPermissions = [];
        foreach ($allowedLayouts as $permission) {
            if (preg_match(sprintf('#^(%s)_(.*)#', $dataObject->getClassId()), $permission, $match)) {
                $userPermissions[] = $match[2];
            }
        }

        return $userPermissions;
    }

    public function getUserAllowedLayouts(DataObject $dataObject, UserInterface $user): array
    {
        /** @var User $user */
        $layoutPermissions = $dataObject->getPermissions(ElementPermissions::CUSTOM_LAYOUT_PERMISSIONS, $user);
        if (!isset($layoutPermissions[ElementPermissions::CUSTOM_LAYOUT_PERMISSIONS])) {
            return [];
        }

        return explode(',', $layoutPermissions[ElementPermissions::CUSTOM_LAYOUT_PERMISSIONS]);
    }
}
