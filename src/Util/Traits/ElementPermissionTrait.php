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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Traits;

use Pimcore\Bundle\StudioBackendBundle\Exception\AccessDeniedException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
trait ElementPermissionTrait
{
    private function isAllowed(
        ElementInterface $element,
        UserInterface $user,
        string $permission
    ): void
    {

        /** @var User $user
         *  Because of isAllowed method in the core :)
         * */
        if (!$element->isAllowed($permission, $user)) {
            throw new AccessDeniedException(
                sprintf('You dont have %s permission', $permission)
            );
        }
    }

}