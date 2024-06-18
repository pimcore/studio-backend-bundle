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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'WorkflowStatus',
    required: ['color', 'colorInverted', 'title', 'label'],
    type: 'object'
)]
final readonly class WorkflowStatus
{
    public function __construct(
        #[Property(
            description: 'color',
            type: 'string',
            example: '#3572b0'
        )]
        private string $color,
        #[Property(
            description: 'colorInverted',
            type: 'boolean',
            example: false
        )]
        private bool $colorInverted,
        #[Property(
            description: 'borderColor',
            type: 'string',
            example: '#ffa500'
        )]
        private string $title,
        #[Property(
            description: 'label',
            type: 'string',
            example: 'Edit Images'
        )]
        private string $label,
    ) {

    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getColorInverted(): bool
    {
        return $this->colorInverted;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
