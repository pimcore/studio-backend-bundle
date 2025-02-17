<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
        #[Property(description: 'Id of custom layout', type: 'string')]
        private readonly string $id,
        #[Property(description: 'Name', type: 'string')]
        private readonly string $name,
        #[Property(description: 'Description', type: 'string')]
        private readonly string $description,
        #[Property(description: 'Creation date timestamp', type: 'integer')]
        private readonly int $creationDate,
        #[Property(description: 'Modification date timestamp', type: 'integer')]
        private readonly int $modificationDate,
        #[Property(description: 'User id of owner', type: 'integer')]
        private readonly int $userOwner,
        #[Property(description: 'Class id', type: 'string')]
        private readonly string $classId,
        #[Property(description: 'Whether it is the default layout', type: 'boolean')]
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

    public function getUserOwner(): int
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
