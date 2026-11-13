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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * Handles the optional `coauthorType`/`coauthor` keys carried by element save payloads.
 *
 * @internal
 */
interface CoauthorServiceInterface
{
    /**
     * Reads and validates the coauthor pair from a save payload. Extraction is deliberately kept
     * separate from running the save so that callers can validate before entering their save
     * try/catch, where an InvalidArgumentException would be masked as a save failure.
     *
     * @return array{type: string, coauthor: string}|null Null when the payload carries no complete pair
     *
     * @throws InvalidArgumentException
     */
    public function extractFromPayload(array $payload): ?array;

    /**
     * Removes the coauthor keys from a save payload that is stored to be saved later (for example by a worker),
     * where the MCP call it came from is no longer known. Outside an MCP tool call the payload is returned as is.
     */
    public function withoutPayloadCoauthorInMcpCall(array $payload): array;

    /**
     * Runs $callback with $coauthor applied to the version coauthor context, restoring the
     * previous context afterwards. A null $coauthor runs the callback unchanged.
     *
     * @param array{type: string, coauthor: string}|null $coauthor
     *
     * @param-immediately-invoked-callable $callback
     *
     * @param callable(): void $callback
     */
    public function runWithCoauthor(?array $coauthor, callable $callback): void;
}
