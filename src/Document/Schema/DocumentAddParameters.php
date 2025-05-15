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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentTypes;

#[Schema(
    title: 'DocumentAdd',
    required: [
        'key', 'type', 'title', 'navigationName', 'docTypeId',
        'translationsSourceId', 'language', 'inheritanceSourceId'
    ],
    type: 'object'
)]
final readonly class DocumentAddParameters
{
    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'my_new_document')]
        private string $key,
        #[Property(description: 'Type', type: 'string', example: DocumentTypes::PAGE->value)]
        private string $type,
        #[Property(description: 'Title', type: 'string', example: 'Some page title')]
        private ?string $title = null,
        #[Property(description: 'Navigation name', type: 'string', example: 'Some navigation name')]
        private ?string $navigationName = null,
        #[Property(description: 'Document type ID', type: 'string', example: DocumentTypes::PAGE->value)]
        private ?string $docTypeId = null,
        #[Property(description: 'Id of the base document for new translation', type: 'integer', example: 33)]
        private ?int $translationsSourceId = null,
        #[Property(description: 'Document language when adding a translation', type: 'string', example: 'en')]
        private ?string $language = null,
        #[Property(description: 'Id of the base document for content', type: 'integer', example: 33)]
        private ?int $inheritanceSourceId = null,
    ) {
        $this->validate();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getNavigationName(): ?string
    {
        return $this->navigationName;
    }

    public function getDocTypeId(): ?string
    {
        return $this->docTypeId;
    }

    public function getTranslationsSourceId(): ?int
    {
        return $this->translationsSourceId;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getInheritanceSourceId(): ?int
    {
        return $this->inheritanceSourceId;
    }

    private function validate(): void
    {
        if (empty($this->getType())) {
            throw new InvalidArgumentException('No document type provided');
        }
    }
}
