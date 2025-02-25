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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

#[Schema(
    title: 'Perspective Config Detail',
    required: [
        'contextPermissions',
        'widgetsLeft',
        'widgetsRight',
        'widgetsBottom',
        'expandedLeft',
        'expandedRight',
    ],
    type: 'object'
)]
final class PerspectiveConfigDetail extends PerspectiveConfig
{
    public function __construct(
        string $id,
        string $name,
        ElementIcon $icon,
        bool $isWriteable = true,
        #[Property(
            description: 'Context Permissions',
            type: 'object',
            example: ['permission_group' => ['permission1' => true, 'permission2' => false]])
        ]
        private readonly array $contextPermissions = [],
        #[Property(description: 'Widgets Left', type: 'array', items: new Items(ref: ElementTreeWidgetConfig::class))]
        private readonly array $widgetsLeft = [],
        #[Property(description: 'Widgets Right', type: 'array', items: new Items(ref: ElementTreeWidgetConfig::class))]
        private readonly array $widgetsRight = [],
        #[Property(description: 'Widgets Bottom', type: 'array', items: new Items(ref: ElementTreeWidgetConfig::class))]
        private readonly array $widgetsBottom = [],
        #[Property(description: 'Left Expanded Widget', type: 'string', example: 'widget_id')]
        private readonly ?string $expandedLeft = null,
        #[Property(description: 'Right Expanded Widget', type: 'string', example: 'widget_id')]
        private readonly ?string $expandedRight = null,
    ) {
        parent::__construct($id, $name, $icon, $isWriteable);
    }

    public function getContextPermissions(): array
    {
        return $this->contextPermissions;
    }

    /**
     * @return WidgetConfig[]
     */
    public function getWidgetsLeft(): array
    {
        return $this->widgetsLeft;
    }

    /**
     * @return WidgetConfig[]
     */
    public function getWidgetsRight(): array
    {
        return $this->widgetsRight;
    }

    /**
     * @return WidgetConfig[]
     */
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
