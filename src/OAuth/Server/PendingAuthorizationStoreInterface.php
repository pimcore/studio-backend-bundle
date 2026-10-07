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
     * The pending authorization's parameters, leaving them in place.
     *
     * For reads that must not end the authorization - the consent screen looking up what it
     * has to show. Completing one goes through {@see self::consume()} instead.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array;

    /**
     * Claims the pending authorization: returns its parameters and removes them in the same
     * step, so of two concurrent callers exactly one is handed the parameters and the other
     * is told there is nothing there.
     *
     * This is the single-use guarantee the authorization-code flow rests on, and it has to
     * be a claim rather than a read followed by a delete: two approvals of one id that both
     * read first would each go on to mint a code.
     *
     * @return array<string, mixed>|null
     */
    public function consume(string $id): ?array;
}
