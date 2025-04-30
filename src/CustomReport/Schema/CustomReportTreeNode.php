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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'Custom Report Tree Node',
    required: ['name', 'niceName', 'iconClass', 'group', 'groupIconClass', 'menuShortcut', 'reportClass'],
    type: 'object'
)]
final class CustomReportTreeNode implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'report name', type: 'string', example: 'Quality_Attributes')]
        private readonly string $name,
        #[Property(description: 'nice name', type: 'string', example: 'Attributes')]
        private readonly string $niceName,
        #[Property(description: 'icon class', type: 'string', example: 'pimcore_icon_attributes')]
        private readonly string $iconClass,
        #[Property(description: 'group', type: 'string', example: 'Quality')]
        private readonly string $group,
        #[Property(description: 'group icon class', type: 'string', example: 'pimcore_group_icon_attributes')]
        private readonly string $groupIconClass,
        #[Property(description: 'menu shortcut', type: 'bool', example: true)]
        private readonly bool $menuShortcut,
        #[Property(description: 'report class', type: 'string', example: '')]
        private readonly string $reportClass
    ) {

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNiceName(): string
    {
        return $this->niceName;
    }

    public function getIconClass(): string
    {
        return $this->iconClass;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getGroupIconClass(): string
    {
        return $this->groupIconClass;
    }

    public function getMenuShortcut(): bool
    {
        return $this->menuShortcut;
    }

    public function getReportClass(): string
    {
        return $this->reportClass;
    }
}
