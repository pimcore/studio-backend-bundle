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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface SecurityServiceInterface
{
    /**
     * @throws UserNotFoundException
     */
    public function getCurrentUser(): UserInterface;

    /**
     * @throws ForbiddenException
     */
    public function hasElementPermission(
        ElementInterface $element,
        UserInterface $user,
        string $permission
    ): void;

    /**
     * @throws ForbiddenException
     *
     * @param array<string> $permissions
     */
    public function hasElementPermissions(
        ElementInterface $element,
        UserInterface $user,
        array $permissions
    ): void;

    public function getSpecialDataObjectPermissions(
        DataObject $dataObject,
        UserInterface $user,
        string $permission
    ): array;
}
