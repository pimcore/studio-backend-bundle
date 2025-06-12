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
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\SettingsDataInterface;

/**
 * @internal
 */
#[Schema(
    title: 'Page Settings Data',
    required: [
        'controller', 'template', 'contentMainDocumentId',
        'contentMainDocumentPath', 'supportsContentMain',
        'staticGeneratorEnabled', 'staticGeneratorLifetime', 'staticLastGenerated', 'url',
    ],
    type: 'object'
)]
readonly class SnippetSettingsData implements SettingsDataInterface
{
    public function __construct(
        #[Property(description: 'Controller', type: 'string', example: 'App\\Controller\\PageController')]
        private ?string $controller,
        #[Property(description: 'Template', type: 'string', example: '@app/template.html.twig')]
        private ?string $template,
        #[Property(description: 'Main document ID', type: 'integer', example: 1)]
        private ?int $contentMainDocumentId,
        #[Property(description: 'Main document path', type: 'string', example: '/path/to/main/document')]
        private ?string $contentMainDocumentPath = null,
        #[Property(description: 'Supports main content', type: 'bool', example: false)]
        private bool $supportsContentMain = false,
        #[Property(description: 'Is static generator enabled', type: 'bool', example: false)]
        private bool $staticGeneratorEnabled = false,
        #[Property(description: 'Lifetime of static generator', type: 'integer', example: 123456)]
        private ?int $staticGeneratorLifetime = null,
        #[Property(description: 'Timestamp of last generated data', type: 'integer', example: 1700000000)]
        private ?int $staticLastGenerated = null,
        #[Property(description: 'Document Url', type: 'string', example: 'https://example.com/')]
        private ?string $url = null,
    ) {
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getContentMainDocumentId(): ?int
    {
        return $this->contentMainDocumentId;
    }

    public function getContentMainDocumentPath(): ?string
    {
        return $this->contentMainDocumentPath;
    }

    public function isSupportsContentMain(): bool
    {
        return $this->supportsContentMain;
    }

    public function isStaticGeneratorEnabled(): bool
    {
        return $this->staticGeneratorEnabled;
    }

    public function getStaticGeneratorLifetime(): ?int
    {
        return $this->staticGeneratorLifetime;
    }

    public function getStaticLastGenerated(): ?int
    {
        return $this->staticLastGenerated;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
