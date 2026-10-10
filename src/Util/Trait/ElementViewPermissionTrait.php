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
use Pimcore\Bundle\StudioBackendBundle\Response\Element;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use function sprintf;

/**
 * @internal
 */
trait ElementViewPermissionTrait
{
    /**
     * The search index can return elements the user may only list, for example parent folders of a workspace.
     * Uses the permissions the index already resolved for the user, so no extra element load is needed.
     *
     * @throws ForbiddenException
     */
    private function assertElementViewPermission(Element $element): void
    {
        if (!$element->getPermissions()->isView()) {
            throw new ForbiddenException(sprintf('You dont have %s permission', ElementPermissions::VIEW_PERMISSION));
        }
    }
}
