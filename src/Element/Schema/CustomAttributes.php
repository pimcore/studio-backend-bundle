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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

#[Schema(
    title: 'CustomAttributes',
    description: 'Custom attributes used mainly for the tree',
    required: ['icon', 'tooltip', 'additionalIcons', 'key', 'additionalCssClasses'],
    type: 'object'
)]
final class CustomAttributes
{
    public function __construct(
        #[Property(description: 'Custom Icon', type: ElementIcon::class)]
        private ?ElementIcon $icon = null,
        #[Property(description: 'Custom Tooltip', type: 'string', example: '<b>My Tooltip</b>')]
        private ?string $tooltip = null,
        #[Property(
            description: 'AdditionalIcons',
            type: 'array',
            items: new Items(type: 'string', example: 'some_other_icon'),
        )]
        private array $additionalIcons = [],
        #[Property(description: 'Custom Key/Filename', type: 'string', example: 'my_custom_key')]
        private ?string $key = null,
        #[Property(
            description: 'Additional Css Classes',
            type: 'array',
            items: new Items(type: 'string', example: 'my_custom_class'),
        )]
        private array $additionalCssClasses = [],
    ) {

    }

    public function getIcon(): ?ElementIcon
    {
        return $this->icon;
    }

    public function setIcon(ElementIcon $icon): void
    {
        $this->icon = $icon;
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    public function setTooltip(string $tooltip): void
    {
        $this->tooltip = $tooltip;
    }

    public function getAdditionalIcons(): array
    {
        return $this->additionalIcons;
    }

    public function setAdditionalIcons(array $icons): void
    {
        $this->additionalIcons = $icons;
    }

    public function addAdditionalIcon(string $value): void
    {
        $this->additionalIcons[] = $value;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getAdditionalCssClasses(): array
    {
        return $this->additionalCssClasses;
    }

    public function setAdditionalCssClasses(array $classes): void
    {
        $this->additionalCssClasses = $classes;
    }
}
