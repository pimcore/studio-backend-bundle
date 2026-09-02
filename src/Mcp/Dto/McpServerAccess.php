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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Dto;

use function array_map;
use function is_array;
use function is_string;

/**
 * Who may access an MCP server. The {@see $owner} (creator) gets implicit read + edit
 * via the resolver and is not listed here; {@see $shareGlobal} is the "public" flag —
 * when set, every authenticated user may read and use (but not edit) the server. Two
 * share grids, keyed by user name and role name, carry per-entry capabilities
 * ({@see McpServerAccessEntry}: canRead / canAccess / canEdit — independent, with edit
 * implying read). Names are used rather than ids so the configuration is portable
 * across instances. Enforcement lives in the access resolver, not here.
 *
 * @internal
 */
final readonly class McpServerAccess
{
    /**
     * @param list<McpServerAccessEntry> $sharedUsers
     * @param list<McpServerAccessEntry> $sharedRoles
     */
    public function __construct(
        public ?string $owner = null,
        public bool $shareGlobal = false,
        public array $sharedUsers = [],
        public array $sharedRoles = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            owner: isset($data['owner']) && is_string($data['owner']) && $data['owner'] !== '' ? $data['owner'] : null,
            shareGlobal: (bool) ($data['share_global'] ?? false),
            sharedUsers: self::entryList($data['shared_users'] ?? []),
            sharedRoles: self::entryList($data['shared_roles'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'owner' => $this->owner,
            'share_global' => $this->shareGlobal,
            'shared_users' => array_map(static fn (McpServerAccessEntry $e): array => $e->toArray(), $this->sharedUsers),
            'shared_roles' => array_map(static fn (McpServerAccessEntry $e): array => $e->toArray(), $this->sharedRoles),
        ];
    }

    /**
     * @param mixed $value
     *
     * @return list<McpServerAccessEntry>
     */
    private static function entryList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $entries = [];
        foreach ($value as $item) {
            $entry = McpServerAccessEntry::fromMixed($item);
            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }
}
