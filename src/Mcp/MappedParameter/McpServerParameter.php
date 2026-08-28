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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\MappedParameter;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Create/update payload for an MCP server. Scopes are not accepted — the backend
 * derives them from the assigned tools' required scope.
 *
 * @internal
 */
final readonly class McpServerParameter
{
    /**
     * @param list<string>                                $tools
     * @param list<array{name?: mixed, canAccess?: mixed, canEdit?: mixed}> $sharedUsers user-name → level share entries
     * @param list<array{name?: mixed, canAccess?: mixed, canEdit?: mixed}> $sharedRoles role-name → level share entries
     */
    public function __construct(
        #[NotBlank]
        private string $name,
        #[NotBlank]
        #[Regex(pattern: '/^[a-z0-9-]+$/', message: 'The url slug may only contain a-z, 0-9 and hyphens.')]
        private string $urlSlug,
        private ?string $description = null,
        private array $tools = [],
        private bool $enabled = true,
        private bool $shareGlobal = false,
        private array $sharedUsers = [],
        private array $sharedRoles = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrlSlug(): string
    {
        return $this->urlSlug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return list<string>
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function shareGlobal(): bool
    {
        return $this->shareGlobal;
    }

    /**
     * @return list<array{name?: mixed, canAccess?: mixed, canEdit?: mixed}>
     */
    public function getSharedUsers(): array
    {
        return $this->sharedUsers;
    }

    /**
     * @return list<array{name?: mixed, canAccess?: mixed, canEdit?: mixed}>
     */
    public function getSharedRoles(): array
    {
        return $this->sharedRoles;
    }
}
