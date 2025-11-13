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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'ElementUsageItem',
    title: 'Element Usage Item',
    required: [
        'id',
        'type',
        'path',
    ],
    type: 'object'
)]
final class ElementUsageItem extends ElementUsageBaseItem implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 9)]
        public readonly int $id,
        #[Property(description: 'type', type: 'string', example: 'object')]
        public readonly string $type,
        #[Property(description: 'path', type: 'string', example: '/Product Data/Cars/jaguar/E-Type')]
        private readonly string $path
    ) {
        parent::__construct($id, $type);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
