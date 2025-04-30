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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Perspective Config',
    required: [
        'id',
        'name',
        'icon',
        'isWriteable',
    ],
    type: 'object'
)]
class PerspectiveConfig implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Perspective ID', type: 'string', example: '5026c239_eb75_499a_8576_841bca283350')]
        private readonly string $id,
        #[Property(description: 'Name', type: 'string', example: 'Cars')]
        private readonly string $name,
        #[Property(description: 'Icon', type: ElementIcon::class)]
        private readonly ElementIcon $icon,
        #[Property(description: 'Is Writeable', type: 'bool', example: true)]
        private readonly bool $isWriteable = true,
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

    public function isWriteable(): bool
    {
        return $this->isWriteable;
    }
}
