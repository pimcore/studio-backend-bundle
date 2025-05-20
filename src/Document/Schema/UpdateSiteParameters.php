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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'Update Site',
    required: [
        'mainDomain',
        'domains',
        'errorDocument',
        'localizedErrorDocuments',
        'redirectToMainDomain',
    ],
    type: 'object'
)]
final readonly class UpdateSiteParameters
{
    public function __construct(
        #[Property(description: 'Main domain', type: 'string', example: 'main_site')]
        private string $mainDomain = '',
        #[Property(description: 'Domains', type: 'array', items: new Items(type: 'string'), example: ['other_domain'])]
        private array $domains = [],
        #[Property(description: 'Error document', type: 'string', example: 'path/to/error/document')]
        private string $errorDocument = '',
        #[Property(
            description: 'Localized error documents',
            type: 'object',
            example: ['en' => 'path/to/en/error/document', 'de' => 'path/to/de/error/document'],
        )]
        private array $localizedErrorDocuments = [],
        #[Property(description: 'Redirect to main domain', type: 'bool', example: false)]
        private bool $redirectToMainDomain = false,
    ) {
    }

    public function getMainDomain(): string
    {
        return $this->mainDomain;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function getErrorDocument(): string
    {
        return $this->errorDocument;
    }

    public function getLocalizedErrorDocuments(): array
    {
        return $this->localizedErrorDocuments;
    }

    public function isRedirectToMainDomain(): bool
    {
        return $this->redirectToMainDomain;
    }
}
