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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Select Option',
    required: [
        'objectReference',
        'formatedPath',
    ],
    type: 'object'
)]
class FormatedPath implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(
            description: 'Object Reference',
            example: 'object_11',
            oneOf: [new Schema(type: 'string'), new Schema(type: 'integer')]
        )]
        private readonly int|string $objectReference,
        #[Property(
            description: 'Formated Path',
            example: 'nice/path',
            oneOf: [new Schema(type: 'string'), new Schema(type: 'integer')]
        )]
        private readonly int|string $formatedPath,
    ) {
    }

    public function getObjectReference(): int|string
    {
        return $this->objectReference;
    }

    public function getFormatedPath(): int|string
    {
        return $this->formatedPath;
    }
}
