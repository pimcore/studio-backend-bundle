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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'BundleCustomReportsTreeNodeFolder',
    title: 'Bundle Custom Reports Tree Node Folder',
    required: [
        'group',
        'groupIconClass',
        'children',
    ],
    type: 'object'
)]
final class CustomReportTreeNodeFolder implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'id', type: 'string', example: 'group_Quality')]
        private readonly string $id,
        #[Property(description: 'group', type: 'string', example: 'Quality')]
        private readonly string $group,
        #[Property(description: 'group icon class', type: 'string', example: 'pimcore_group_icon_attributes')]
        private readonly string $groupIconClass,
        #[Property(
            description: 'Child nodes',
            type: 'array',
            items: new Items(ref: CustomReportTreeConfigNode::class)
        )]
        private readonly array $children = [],
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getGroupIconClass(): string
    {
        return $this->groupIconClass;
    }

    /**
     * @return CustomReportTreeConfigNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
