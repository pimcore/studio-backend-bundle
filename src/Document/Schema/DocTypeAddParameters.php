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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentTypes;

/**
 * @internal
 */
#[Schema(
    title: 'DocTypeAdd',
    required: ['name', 'type'],
    type: 'object'
)]
final readonly class DocTypeAddParameters
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'New Document Type')]
        private string $name = 'New Document Type',
        #[Property(description: 'Type', type: 'string', example: DocumentTypes::PAGE->value)]
        private string $type = DocumentTypes::PAGE->value,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
