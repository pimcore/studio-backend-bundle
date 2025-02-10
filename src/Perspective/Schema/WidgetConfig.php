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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Widget Config',
    required: [
        'id',
        'name',
        'widgetType',
        'icon',
    ],
    type: 'object'
)]
class WidgetConfig implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Widget ID', type: 'string', example: '5026c239_eb75_499a_8576_841bca283350')]
        private readonly string $id,
        #[Property(description: 'Name', type: 'string', example: 'Cars')]
        private readonly string $name,
        #[Property(description: 'Widget Type', type: 'string', example: 'element_trees')]
        private readonly string $widgetType,
        #[Property(description: 'Icon', type: ElementIcon::class)]
        private readonly ElementIcon $icon,
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

    public function getWidgetType(): string
    {
        return $this->widgetType;
    }

    public function getIcon(): ElementIcon
    {
        return $this->icon;
    }
}
