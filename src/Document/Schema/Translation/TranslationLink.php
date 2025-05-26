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

/**
 * @internal
 */
#[Schema(
    schema: 'Document Translation Link',
    required: ['language', 'documentId'],
    type: 'object'
)]
final readonly class TranslationLink
{
    public function __construct(
        #[Property(description: 'Language', type: 'string', example: 'en')]
        private string $language,
        #[Property(description: 'Document Id', type: 'int', example: 83)]
        private int $documentId,
    ) {
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }
}
