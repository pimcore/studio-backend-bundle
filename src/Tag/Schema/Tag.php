<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'Tag',
    type: 'object'
)]
final class Tag implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'tag ID', type: 'integer', example: 2)]
        private readonly int $id,
        #[Property(description: 'parent tag ID', type: 'integer', example: 0)]
        private readonly int $parentId,
        #[Property(description: 'tag text', type: 'string', example: 'Tag 1')]
        private readonly string $text,
        #[Property(description: 'path', type: 'string', example: '/test')]
        private readonly string $path,
        #[Property(description: 'has children', type: 'bool', example: false)]
        private readonly bool $hasChildren,
        #[Property(description: 'IconName', type: 'string', example: 'pimcore_icon_pdf')]
        private readonly string $iconName,
        #[Property(description: 'children', type: 'array', items: new Items(type: Tag::class, example: new Tag(id: 3, parentId: 0, text: 'Tag 2', path: '/test/2', hasChildren: false, iconName: 'pimcore_icon_pdf')))]
        private array $children = [],
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function isHasChildren(): bool
    {
        return $this->hasChildren;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param array<int, Tag> $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function addChild(Tag $child): void
    {
        $this->children[] = $child;
    }

    public function getIconName(): string
    {
        return $this->iconName;
    }
}
