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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Exception;

use DomainException;

/**
 * Thrown when a client for a service user cannot be registered or its secret cannot be
 * rotated: the service user is unsaved, already has a client, has none to rotate, or a
 * scope is unknown.
 *
 * @internal
 */
final class ServiceUserClientException extends DomainException
{
}
