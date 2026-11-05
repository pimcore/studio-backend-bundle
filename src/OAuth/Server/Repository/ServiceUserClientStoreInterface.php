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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;

/**
 * Storage for the confidential client_credentials clients bound one to one to a service
 * user. They live in the same table as dynamically registered clients, so the client
 * repository resolves them through DynamicClientStoreInterface::find() like any other.
 *
 * @internal
 */
interface ServiceUserClientStoreInterface
{
    /**
     * Stores a new bound client. The client must carry a service user id.
     */
    public function save(DynamicClient $client): void;

    public function findByServiceUserId(int $serviceUserId): ?DynamicClient;

    /**
     * Replaces the client's secret hash in place, so the previous secret stops validating
     * with the next token request. Does nothing for an unknown client.
     */
    public function replaceSecretHash(string $identifier, string $secretHash): void;
}
