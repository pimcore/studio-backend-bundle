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

use function is_array;
use function is_numeric;

/**
 * Who may use an MCP server. Mirrors the bundle's SavedSearch/Grid sharing model
 * (owner + global flag + explicit user/role id lists); users and roles are kept
 * in separate lists so a shared id is never ambiguous. Enforcement lives in the
 * access resolver, not here.
 *
 * @internal
 */
final readonly class McpServerAccess
{
    /**
     * @param list<int> $sharedUsers
     * @param list<int> $sharedRoles
     */
    public function __construct(
        public ?int $owner = null,
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
            owner: isset($data['owner']) && is_numeric($data['owner']) ? (int) $data['owner'] : null,
            shareGlobal: (bool) ($data['share_global'] ?? false),
            sharedUsers: self::intList($data['shared_users'] ?? []),
            sharedRoles: self::intList($data['shared_roles'] ?? []),
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
            'shared_users' => $this->sharedUsers,
            'shared_roles' => $this->sharedRoles,
        ];
    }

    /**
     * @param mixed $value
     *
     * @return list<int>
     */
    private static function intList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            if (is_numeric($item)) {
                $ids[] = (int) $item;
            }
        }

        return $ids;
    }
}
