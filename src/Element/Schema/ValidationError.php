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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'ValidationError',
    description: 'A single validation error of an element, with the data needed to translate it',
    required: ['path', 'message', 'parameters'],
    type: 'object'
)]
final readonly class ValidationError
{
    /**
     * @param list<ValidationErrorPath> $path innermost level first
     * @param array<string, scalar|null> $parameters
     */
    public function __construct(
        #[Property(description: 'Name of the field that failed', type: 'string', example: 'title', nullable: true)]
        private ?string $field,
        #[Property(description: 'Raw, untranslated field title', type: 'string', example: 'Title', nullable: true)]
        private ?string $fieldTitle,
        #[Property(
            description: 'Location of the field, innermost level first',
            type: 'array',
            items: new Items(ref: ValidationErrorPath::class)
        )]
        private array $path,
        #[Property(description: 'Untranslated message', type: 'string', example: 'Empty mandatory field [ title ]')]
        private string $message,
        #[Property(description: 'Translation key', type: 'string', example: 'validation.mandatory', nullable: true)]
        private ?string $messageKey,
        #[Property(
            description: 'Translation parameters',
            type: 'object',
            example: ['max' => 10],
            additionalProperties: true
        )]
        private array $parameters,
    ) {
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function getFieldTitle(): ?string
    {
        return $this->fieldTitle;
    }

    /**
     * @return list<ValidationErrorPath>
     */
    public function getPath(): array
    {
        return $this->path;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getMessageKey(): ?string
    {
        return $this->messageKey;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
