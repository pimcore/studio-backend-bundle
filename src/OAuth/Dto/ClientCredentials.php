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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Dto;

use SensitiveParameter;

/**
 * A client id with its plain secret, returned once when a service-user client is
 * registered or its secret rotated. Only the secret's hash is stored; never log or
 * persist this object.
 *
 * @internal
 */
final readonly class ClientCredentials
{
    public function __construct(
        public string $clientId,
        #[SensitiveParameter]
        public string $clientSecret,
    ) {
    }
}
