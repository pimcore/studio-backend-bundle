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

namespace Pimcore\Bundle\StudioBackendBundle\Exception;

use LogicException;

/**
 * A contributed notification channel is not usable: a duplicate name, or one colliding with
 * the reserved in-app pop-up preference. Always a wiring mistake in a contributing bundle.
 *
 * @internal
 */
final class InvalidNotificationChannelException extends LogicException
{
}
