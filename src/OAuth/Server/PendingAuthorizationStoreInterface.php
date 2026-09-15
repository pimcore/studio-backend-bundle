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

/**
 * Holds a validated authorization request between the redirect to the consent screen and
 * the user's decision, keyed by an opaque id carried in the URL.
 *
 * @internal
 */
interface PendingAuthorizationStoreInterface
{
    /**
     * @param array<string, mixed> $queryParams
     */
    public function store(string $id, array $queryParams): void;

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array;

    public function remove(string $id): void;
}
