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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'SavedSearchConfigurationListItem',
    title: 'Saved Search Configuration List Item',
    required: ['id', 'name', 'owner', 'modificationDate'],
    type: 'object'
)]
final class ConfigurationListItem implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 42)]
        private readonly int $id,
        #[Property(description: 'Name', type: 'string', example: 'My Configuration')]
        private readonly string $name,
        #[Property(description: 'Description', type: 'string', example: 'My Configuration Description')]
        private readonly ?string $description,
        #[Property(
            description: 'Whether the configuration is owned by the current user (false if only shared)',
            type: 'boolean',
            example: true
        )]
        private readonly bool $owner,
        #[Property(description: 'Modification Date', type: 'integer', example: 1634025600)]
        private readonly int $modificationDate,
        #[Property(description: 'Creation Date', type: 'integer', example: 1634025600)]
        private readonly int $creationDate,
        #[Property(
            description: 'Name of the group in the menu the shortcut belongs to',
            type: 'string',
            example: 'My Group',
            nullable: true
        )]
        private readonly ?string $menuShortcutGroup = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isOwner(): bool
    {
        return $this->owner;
    }

    public function getModificationDate(): int
    {
        return $this->modificationDate;
    }

    public function getCreationDate(): int
    {
        return $this->creationDate;
    }

    public function getMenuShortcutGroup(): ?string
    {
        return $this->menuShortcutGroup;
    }
}
