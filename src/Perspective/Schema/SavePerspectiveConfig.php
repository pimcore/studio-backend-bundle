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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

/**
 * @internal
 */
#[Schema(
    title: 'Create Perspective Config',
    required: [
        'id',
        'name',
        'icon',
        'contextPermissions',
        'widgetsLeft',
        'widgetsRight',
        'widgetsBottom',
        'expandedLeft',
        'expandedRight'
    ],
    type: 'object'
)]
final readonly class SavePerspectiveConfig
{
    public function __construct(
        #[Property(description: 'Perspective ID', type: 'string', example: '5026c239_eb75_499a_8576_841bca283350')]
        private string $id,
        #[Property(description: 'Name', type: 'string', example: 'Cars')]
        private string $name,
        #[Property(description: 'Icon', type: ElementIcon::class)]
        private ElementIcon $icon,
        #[Property(description: 'Context Permissions', type: 'object',)]
        private array $contextPermissions = [],
        #[Property(description: 'Widgets Left', type: 'object', example: ['widget_id'])]
        private array $widgetsLeft = [],
        #[Property(description: 'Widgets Right', type: 'object', example: ['widget_id'])]
        private array $widgetsRight = [],
        #[Property(description: 'Widgets Bottom', type: 'object', example: ['widget_id'])]
        private array $widgetsBottom = [],
        #[Property(description: 'Left Expanded Widget', type: 'string', example: 'widget_id')]
        private ?string $expandedLeft = null,
        #[Property(description: 'Right Expanded Widget', type: 'string', example: 'widget_id')]
        private ?string $expandedRight = null
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

    public function getIcon(): ElementIcon
    {
        return $this->icon;
    }

    public function getContextPermissions(): array
    {
        return $this->contextPermissions;
    }

    public function getWidgetsLeft(): array
    {
        return $this->widgetsLeft;
    }

    public function getWidgetsRight(): array
    {
        return $this->widgetsRight;
    }

    public function getWidgetsBottom(): array
    {
        return $this->widgetsBottom;
    }

    public function getExpandedLeft(): ?string
    {
        return $this->expandedLeft;
    }

    public function getExpandedRight(): ?string
    {
        return $this->expandedRight;
    }
}
