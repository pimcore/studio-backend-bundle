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
use function is_string;

/**
 * One share entry on an MCP server: a user or role, identified by its (unique)
 * name, paired with the level it is granted. Names are used rather than ids so a
 * configuration stays portable across instances where the same user/role may
 * carry a different id. The kind (user vs role) is carried by which list the
 * entry lives in on {@see McpServerAccess}.
 *
 * @internal
 */
final readonly class McpServerAccessEntry
{
    public function __construct(
        public string $name,
        public McpServerPermission $permission,
    ) {
    }

    /**
     * Tolerant deserialization: a bare string name is read as a
     * {@see McpServerPermission::Read} grant; a `{name, permission}` map carries
     * its level. Returns null for anything without a usable name.
     *
     * @param mixed $value
     */
    public static function fromMixed(mixed $value): ?self
    {
        if (is_string($value) && $value !== '') {
            return new self($value, McpServerPermission::Read);
        }

        if (!is_array($value) || !isset($value['name']) || !is_string($value['name']) || $value['name'] === '') {
            return null;
        }

        $permission = McpServerPermission::tryFrom(is_string($value['permission'] ?? null) ? $value['permission'] : '');

        return new self($value['name'], $permission ?? McpServerPermission::Read);
    }

    /**
     * @return array{name: string, permission: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'permission' => $this->permission->value,
        ];
    }
}
