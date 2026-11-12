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
 * A contributed notification type descriptor is not usable: a duplicate id, or an id longer
 * than the `notifications`.`type` column allows. Always a wiring mistake in a contributing
 * bundle, never a runtime condition — hence LogicException.
 *
 * @internal
 */
final class InvalidNotificationTypeException extends LogicException
{
}
