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

/**
 * @internal
 */
#[Schema(
    title: 'ValidationErrorPath',
    description: 'One level of the location of a validation error, e.g. a localized field or an object brick',
    required: ['field'],
    type: 'object'
)]
final readonly class ValidationErrorPath
{
    public function __construct(
        #[Property(description: 'Field name of this level', type: 'string', example: 'localizedfields')]
        private string $field,
        #[Property(description: 'Raw, untranslated title', type: 'string', example: 'Sale information', nullable: true)]
        private ?string $title = null,
        #[Property(description: 'Language of this level', type: 'string', example: 'en', nullable: true)]
        private ?string $language = null,
        #[Property(description: 'Row index within a block or field collection', type: 'integer', nullable: true)]
        private ?int $index = null,
        #[Property(description: 'Type of this level, e.g. objectbrick', type: 'string', nullable: true)]
        private ?string $type = null,
    ) {
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
}
