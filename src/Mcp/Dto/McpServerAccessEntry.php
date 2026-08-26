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

use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerPermission;
use function is_array;
use function is_numeric;

/**
 * One share entry on an MCP server: a user or role id paired with the level it is
 * granted. The kind (user vs role) is carried by which list the entry lives in on
 * {@see McpServerAccess}, so it is not repeated here.
 *
 * @internal
 */
final readonly class McpServerAccessEntry
{
    public function __construct(
        public int $id,
        public McpServerPermission $permission,
    ) {
    }

    /**
     * Tolerant deserialization from stored/submitted data. A bare numeric id is
     * read as a {@see McpServerPermission::Read} grant — this is the compatibility
     * bridge for the earlier "flat id list" shape, whose entries meant "may use".
     * Returns null for anything without a usable id.
     *
     * @param mixed $value
     */
    public static function fromMixed(mixed $value): ?self
    {
        if (is_numeric($value)) {
            return new self((int) $value, McpServerPermission::Read);
        }

        if (!is_array($value) || !isset($value['id']) || !is_numeric($value['id'])) {
            return null;
        }

        $permission = is_numeric($value['permission'] ?? null)
            ? null
            : McpServerPermission::tryFrom((string) ($value['permission'] ?? ''));

        return new self((int) $value['id'], $permission ?? McpServerPermission::Read);
    }

    /**
     * @return array{id: int, permission: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'permission' => $this->permission->value,
        ];
    }
}
