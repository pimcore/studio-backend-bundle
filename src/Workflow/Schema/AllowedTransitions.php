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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'AllowedTransitions',
    type: 'object'
)]
final readonly class AllowedTransitions
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
            items: new Items(),
            example: '[some notes]')
        ]
        private ?array $notes,
        #[Property(description: 'unsavedChangesBehaviour', type: 'string', example: 'warn')]
        private string $unsavedChangesBehaviour,
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

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getUnsavedChangesBehaviour(): string
    {
        return $this->unsavedChangesBehaviour;
    }
}