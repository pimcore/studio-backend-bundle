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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema\Translation;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'Document Translation Parent',
    required: ['id', 'fullPath'],
    type: 'object'
)]
final class TranslationParent implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Document Id', type: 'int', example: 83)]
        private readonly int $id,
        #[Property(description: 'Document full path', type: 'string', example: '/path/to/document')]
        private readonly string $fullPath,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }
}
