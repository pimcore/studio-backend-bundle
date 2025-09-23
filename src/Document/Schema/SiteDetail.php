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

use OpenApi\Attributes\AdditionalProperties;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'SiteDetailData',
    title: 'Site Detail Data',
    required: [
        'id', 'creationDate', 'modificationDate', 'mainDomain', 'domains',
        'errorDocument', 'localizedErrorDocuments', 'redirectToMainDomain',
    ],
    type: 'object'
)]
final class SiteDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 0)]
        private readonly int $id,
        #[Property(description: 'Creation Date', type: 'integer', example: 1712345678)]
        private readonly ?int $creationDate,
        #[Property(description: 'Modification Date', type: 'integer', example: 1712345678)]
        private readonly ?int $modificationDate,
        #[Property(description: 'Main domain', type: 'string', example: 'main_site')]
        private readonly string $mainDomain = '',
        #[Property(description: 'Domains', type: 'array', items: new Items(type: 'string'), example: ['other_domain'])]
        private readonly array $domains = [],
        #[Property(ref: RelatedElementData::class, description: 'Data of error document', type: 'object')]
        private readonly ?RelatedElementData $errorDocument = null,
        #[Property(
            description: 'Localized error documents mapped by locale',
            type: 'object',
            example: [
                'en' => [
                    'id' => 123,
                    'type' => 'document',
                    'subtype' => 'page',
                    'fullPath' => 'en/error-page',
                    'isPublished' => true
                ],
            ],
            additionalProperties: new AdditionalProperties(
                ref: RelatedElementData::class,
                description: 'Error document data for locale'
            ),
        )]
        private readonly array $localizedErrorDocuments = [],
        #[Property(description: 'Redirect to main domain', type: 'bool', example: false)]
        private readonly bool $redirectToMainDomain = false,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getMainDomain(): string
    {
        return $this->mainDomain;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function getErrorDocument(): ?RelatedElementData
    {
        return $this->errorDocument;
    }

    /**
     * @return array<string, RelatedElementData>
     */
    public function getLocalizedErrorDocuments(): array
    {
        return $this->localizedErrorDocuments;
    }

    public function isRedirectToMainDomain(): bool
    {
        return $this->redirectToMainDomain;
    }
}
