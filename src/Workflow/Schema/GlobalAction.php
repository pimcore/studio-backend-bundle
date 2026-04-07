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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'GlobalAction',
    required: ['name', 'label', 'iconCls', 'objectLayout', 'notes'],
    type: 'object'
)]
final readonly class GlobalAction
{
    public function __construct(
        #[Property(description: 'name', type: 'string', example: 'start_workflow')]
        private string $name,
        #[Property(description: 'label', type: 'string', example: 'Start Workflow')]
        private string $label,
        #[Property(description: 'iconCls', type: 'string', example: 'pimcore_workflow_start')]
        private string $iconCls,
        #[Property(description: 'objectLayout', type: 'bool', example: false)]
        private bool $objectLayout,
        #[Property(
            description: 'notes',
            type: 'array',
            items: new Items(type: 'object'),
            example: ['commentEnabled' => true, 'commentRequired' => true],
        )]
        private array $notes = [],
    ) {

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIconCls(): string
    {
        return $this->iconCls;
    }

    public function getObjectLayout(): bool
    {
        return $this->objectLayout;
    }

    public function getNotes(): array
    {
        return $this->notes;
    }
}
