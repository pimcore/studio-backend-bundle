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

use RuntimeException;

/**
 * Registration failure carrying an RFC 7591 error code
 * (`invalid_redirect_uri` or `invalid_client_metadata`).
 *
 * @internal
 */
final class ClientRegistrationException extends RuntimeException
{
    public function __construct(
        private readonly string $error,
        string $description,
    ) {
        parent::__construct($description);
    }

    public function getError(): string
    {
        return $this->error;
    }
}
