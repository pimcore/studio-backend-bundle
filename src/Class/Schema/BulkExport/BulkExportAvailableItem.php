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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkExport;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'BulkExportAvailableItem',
    title: 'Bulk Export Available Item',
    required: ['type', 'name', 'displayName', 'icon'],
    type: 'object'
)]
final class BulkExportAvailableItem implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Definition type', type: 'string', example: 'class')]
        private readonly string $type,
        #[Property(description: 'Definition name or identifier', type: 'string', example: 'Car')]
        private readonly string $name,
        #[Property(description: 'Human-readable display name', type: 'string', example: 'Car')]
        private readonly string $displayName,
        #[Property(description: 'Icon identifier', type: 'string', example: 'class')]
        private readonly string $icon,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
