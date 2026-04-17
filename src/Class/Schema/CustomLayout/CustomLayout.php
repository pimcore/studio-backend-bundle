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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Layout;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'CustomLayout',
    title: 'Custom layouts',
    required: [
        'id',
        'name',
        'description',
        'creationDate',
        'modificationDate',
        'userOwner',
        'classId',
        'default',
        'layoutDefinition',
    ],
    type: 'object'
)]
final class CustomLayout implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Id of custom layout', type: 'string', example: 'custom_layout_1')]
        private readonly string $id,
        #[Property(description: 'Name', type: 'string', example: 'Custom Layout 1')]
        private readonly string $name,
        #[Property(description: 'Description', type: 'string', example: 'This is a custom layout')]
        private readonly string $description,
        #[Property(description: 'Creation date timestamp', type: 'integer', example: 1633036800)]
        private readonly int $creationDate,
        #[Property(description: 'Modification date timestamp', type: 'integer', example: 1633036800)]
        private readonly int $modificationDate,
        #[Property(description: 'User id of owner', type: 'integer', example: 1)]
        private readonly ?int $userOwner,
        #[Property(description: 'Class id', type: 'string', example: 'Product')]
        private readonly string $classId,
        #[Property(description: 'Whether it is the default layout', type: 'boolean', example: false)]
        private readonly bool $default = false,
        #[Property(ref: Layout::class, description: 'Layout definitions', type: 'object')]
        private readonly ?Layout $layoutDefinition = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreationDate(): int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): int
    {
        return $this->modificationDate;
    }

    public function getUserOwner(): ?int
    {
        return $this->userOwner;
    }

    public function getClassId(): string
    {
        return $this->classId;
    }

    public function getLayoutDefinition(): ?Layout
    {
        return $this->layoutDefinition;
    }
}
