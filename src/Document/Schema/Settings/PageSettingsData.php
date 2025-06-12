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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema\Settings;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Page Settings Data',
    required: ['title', 'description', 'prettyUrl'],
    type: 'object'
)]
final readonly class PageSettingsData extends SnippetSettingsData
{
    public function __construct(
        #[Property(description: 'Title', type: 'string', example: 'Link Title')]
        private ?string $title,
        #[Property(description: 'Description', type: 'string', example: 'Link Description')]
        private ?string $description,
        #[Property(description: 'Pretty Url', type: 'string', example: 'pretty/url')]
        private ?string $prettyUrl,
        ?string $controller,
        ?string $template,
        ?int $contentMainDocumentId,
        ?string $contentMainDocumentPath,
        bool $supportsContentMain,
        bool $staticGeneratorEnabled,
        ?int $staticGeneratorLifetime,
        ?int $staticLastGenerated,
        ?string $url,
    ) {
        parent::__construct(
            controller: $controller,
            template: $template,
            contentMainDocumentId: $contentMainDocumentId,
            contentMainDocumentPath: $contentMainDocumentPath,
            supportsContentMain: $supportsContentMain,
            staticGeneratorEnabled: $staticGeneratorEnabled,
            staticGeneratorLifetime: $staticGeneratorLifetime,
            staticLastGenerated: $staticLastGenerated,
            url: $url
        );
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrettyUrl(): ?string
    {
        return $this->prettyUrl;
    }
}
