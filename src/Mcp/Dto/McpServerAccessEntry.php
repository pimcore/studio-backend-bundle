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
use function is_string;

/**
 * One share entry on an MCP server: a user or role (by unique name) with three
 * independent capabilities — {@see $canRead} (see the server and its config),
 * {@see $canAccess} (connect a client at runtime), and {@see $canEdit} (change the
 * config). The capabilities do not imply one another, with a single invariant:
 * {@see $canEdit} always implies {@see $canRead} (never edit-without-read). The kind
 * (user vs role) is carried by which list the entry lives in on {@see McpServerAccess}.
 *
 * @internal
 */
final readonly class McpServerAccessEntry
{
    public bool $canRead;

    public function __construct(
        public string $name,
        bool $canRead = true,
        public bool $canAccess = false,
        public bool $canEdit = false,
    ) {
        // Edit implies read — there is no edit-without-read grant.
        $this->canRead = $canRead || $canEdit;
    }

    /**
     * Tolerant deserialization from stored (snake_case) or submitted (camelCase)
     * data. A bare string name is a read-only entry. `canRead` defaults to true so
     * grants stored before this flag existed keep their "listed = read" behavior.
     * Returns null for anything without a usable name.
     *
     * @param mixed $value
     */
    public static function fromMixed(mixed $value): ?self
    {
        if (is_string($value) && $value !== '') {
            return new self($value);
        }

        if (!is_array($value) || !isset($value['name']) || !is_string($value['name']) || $value['name'] === '') {
            return null;
        }

        return new self(
            $value['name'],
            (bool) ($value['canRead'] ?? $value['can_read'] ?? true),
            (bool) ($value['canAccess'] ?? $value['can_access'] ?? false),
            (bool) ($value['canEdit'] ?? $value['can_edit'] ?? false),
        );
    }

    /**
     * @return array{name: string, can_read: bool, can_access: bool, can_edit: bool}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'can_read' => $this->canRead,
            'can_access' => $this->canAccess,
            'can_edit' => $this->canEdit,
        ];
    }
}
