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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'SettingsConfigurationData',
    title: 'Settings Configuration Data',
    required: ['id', 'name', 'icon'],
    type: 'object'
)]
class Config implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Configuration ID', type: 'string', example: 'my-config')]
        private readonly string $id,
        #[Property(description: 'Configuration name', type: 'string', example: 'My Configuration')]
        private readonly string $name,
        #[Property(description: 'Configuration icon', type: ElementIcon::class)]
        private readonly ElementIcon $icon
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
}
