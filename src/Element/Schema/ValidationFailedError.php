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
    title: 'ValidationFailedError',
    description: 'Error response of a failed element validation',
    required: ['message'],
    type: 'object'
)]
final readonly class ValidationFailedError
{
    /**
     * @param list<ValidationError> $validationErrors
     */
    public function __construct(
        #[Property(
            description: 'Message of the failure in the language of the server, can contain HTML (e.g. an image that is too large)',
            type: 'string',
            example: 'Validation failed: Empty mandatory field'
        )]
        private string $message,
        #[Property(
            description: 'Error key, not present when the request failed before the element was validated',
            type: 'string',
            example: 'error_element_validation_failed'
        )]
        private ?string $errorKey = null,
        #[Property(
            description: 'Individual validation errors, present when the failure came from the element validation',
            type: 'array',
            items: new Items(ref: ValidationError::class)
        )]
        private array $validationErrors = [],
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getErrorKey(): ?string
    {
        return $this->errorKey;
    }

    /**
     * @return list<ValidationError>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
