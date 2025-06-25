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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'CustomLayoutCompact',
    title: 'Custom layouts in compact format to be used for e.g. listings',
    required: [
        'id',
        'name',
        'default',
    ],
    type: 'object'
)]
final class CustomLayoutCompact implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Id of custom layout', type: 'string', example: 'custom_layout_1')]
        private readonly string $id,
        #[Property(description: 'Name', type: 'string', example: 'Custom Layout 1')]
        private readonly string $name,
        #[Property(description: 'Whether it is the default layout', type: 'boolean', example: false)]
        private readonly bool $default
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

    public function isDefault(): bool
    {
        return $this->default;
    }
}
