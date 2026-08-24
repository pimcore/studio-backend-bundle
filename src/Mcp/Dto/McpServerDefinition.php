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
 * One configured MCP server: a named group of tools exposed at
 * `/pimcore-mcp/{urlSlug}`. Persisted through the location-aware config
 * repository; {@see fromArray()}/{@see toArray()} are the (un)serialization
 * boundary, so both the shipped symfony-config seed and the settings-store JSON
 * map onto the same shape.
 *
 * @internal
 */
final readonly class McpServerDefinition
{
    /**
     * @param list<string> $toolIds identifiers of the assigned MCP tools
     * @param list<string> $scopes  OAuth scopes advertised for this server (e.g. mcp:read/mcp:write)
     */
    public function __construct(
        public string $id,
        public string $displayName,
        public string $description,
        public string $urlSlug,
        public array $toolIds,
        public array $scopes,
        public bool $enabled,
        public McpServerAccess $access,
    ) {
    }

    /**
     * @param array<string, mixed> $data the stored config data (without the id, which is the key)
     */
    public static function fromArray(string $id, array $data): self
    {
        return new self(
            id: $id,
            displayName: isset($data['name']) && is_string($data['name']) ? $data['name'] : $id,
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : '',
            urlSlug: isset($data['url_slug']) && is_string($data['url_slug']) && $data['url_slug'] !== ''
                ? $data['url_slug']
                : $id,
            toolIds: self::stringList($data['tools'] ?? []),
            scopes: self::stringList($data['scopes'] ?? []),
            enabled: (bool) ($data['enabled'] ?? true),
            access: McpServerAccess::fromArray(is_array($data['access'] ?? null) ? $data['access'] : []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->displayName,
            'description' => $this->description,
            'url_slug' => $this->urlSlug,
            'tools' => $this->toolIds,
            'scopes' => $this->scopes,
            'enabled' => $this->enabled,
            'access' => $this->access->toArray(),
        ];
    }

    /**
     * @param mixed $value
     *
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }
}
