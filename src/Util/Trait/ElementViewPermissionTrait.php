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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function sprintf;

/**
 * Requires the using class to provide `$serviceResolver` and to use the ElementProviderTrait.
 *
 * @internal
 */
trait ElementViewPermissionTrait
{
    /**
     * The search index can return elements the user may only list, for example parent folders of a workspace.
     * The core permission check is authoritative and independent of index state and caches.
     *
     * Elements the user may not even list are reported as not found, so ids cannot be probed.
     *
     * @throws ForbiddenException|NotFoundException
     */
    private function assertElementViewPermission(string $elementType, int $id, UserInterface $user): void
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $id);

        /** @var User $user */
        if ($element->isAllowed(ElementPermissions::VIEW_PERMISSION, $user)) {
            return;
        }

        if (!$element->isAllowed(ElementPermissions::LIST_PERMISSION, $user)) {
            throw new NotFoundException($elementType, $id);
        }

        throw new ForbiddenException(sprintf('You dont have %s permission', ElementPermissions::VIEW_PERMISSION));
    }

    /**
     * @throws NotFoundException
     */
    private function isElementViewAllowed(string $elementType, int $id, UserInterface $user): bool
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $id);

        /** @var User $user */
        return $element->isAllowed(ElementPermissions::VIEW_PERMISSION, $user);
    }
}
