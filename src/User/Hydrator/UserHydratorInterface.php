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

namespace Pimcore\Bundle\StudioBackendBundle\User\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\User\Schema\User as UserSchema;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface UserHydratorInterface
{
    public function hydrate(UserInterface $user): UserSchema;
}