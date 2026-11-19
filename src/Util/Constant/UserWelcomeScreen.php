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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

/**
 * Column width of the core `users`.`welcomeScreenTarget`, also enforced at the API boundary.
 *
 * @internal
 */
interface UserWelcomeScreen
{
    public const int MAX_TARGET_LENGTH = 255;
}
