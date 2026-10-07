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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server;

use JsonException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\RegisteredClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ClientRegistrationException;

/**
 * @internal
 */
interface ClientRegistrarInterface
{
    /**
     * Validates an RFC 7591 registration request and returns the client it created, or the
     * one an identical earlier request already created.
     *
     * @param array<string, mixed> $metadata
     *
     * @throws ClientRegistrationException
     * @throws JsonException
     */
    public function register(array $metadata): RegisteredClient;
}
