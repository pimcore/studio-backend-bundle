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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\Document\PageSearchPreview;

#[Schema(
    title: 'SimpleSearchDocumentDetail',
    required: ['language', 'documentData'],
    type: 'object'
)]
final class DocumentSearchPreview extends SimpleSearchPreview
{
    public function __construct(
        int $id,
        string $elementType,
        string $type,
        ?int $userOwner,
        ?string $userOwnerName,
        ?int $userModification,
        ?string $userModificationName,
        ?int $creationDate,
        ?int $modificationDate,
        #[Property(description: 'Document Language', type: 'string', example: 'English')]
        private readonly ?string $language,
        #[Property(description: 'Page document data', type: PageSearchPreview::class)]
        private readonly ?PageSearchPreview $documentData = null,
    ) {
        parent::__construct(
            $id,
            $elementType,
            $type,
            $userOwner,
            $userOwnerName,
            $userModification,
            $userModificationName,
            $creationDate,
            $modificationDate
        );
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getDocumentData(): ?PageSearchPreview
    {
        return $this->documentData;
    }
}
