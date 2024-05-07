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
    type: 'object'
)]
final readonly class WorkflowStatus
{
    public function __construct(
        #[Property(
            description: 'backgroundColor',
            type: 'string',
            example: '#FF0000'
        )]
        private string $backgroundColor,
        #[Property(
            description: 'fontColor',
            type: 'string',
            example: '#FF0000'
        )]
        private string $fontColor,
        #[Property(
            description: 'borderColor',
            type: 'string',
            example: '#FF0000'
        )]
        private string $borderColor,
        #[Property(
            description: 'title',
            type: 'string',
            example: 'status'
        )]
        private string $title,
        #[Property(
            description: 'label',
            type: 'string',
            example: 'Status'
        )]
        private string $label,
    ) {

    }

    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    public function getFontColor(): string
    {
        return $this->fontColor;
    }

    public function getBorderColor(): string
    {
        return $this->borderColor;
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