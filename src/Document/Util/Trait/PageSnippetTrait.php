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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Util\Trait;

use OpenApi\Attributes\Property;

/**
 * @internal
 */
trait PageSnippetTrait
{
    #[Property(description: 'Controller', type: 'string', example: 'App\\Controller\\PageController')]
    private ?string $controller;
    #[Property(description: 'Template', type: 'string', example: 'App\\Controller\\PageController')]
    private ?string $template;
    #[Property(description: 'Main document ID', type: 'integer', example: 1)]
    private ?int $contentMainDocumentId;
    #[Property(description: 'Supports main content', type: 'bool', example: false)]
    private bool $supportsContentMain = false;
    #[Property(description: 'Is missing required editable', type: 'bool', example: false)]
    private bool $missingRequiredEditable = false;
    #[Property(description: 'Is static generator enabled', type: 'bool', example: false)]
    private bool $staticGeneratorEnabled = false;
    #[Property(description: 'Lifetime of static generator', type: 'integer', example: 123456)]
    private ?int $staticGeneratorLifetime;

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

    public function isSupportsContentMain(): bool
    {
        return $this->supportsContentMain;
    }

    public function isMissingRequiredEditable(): bool
    {
        return $this->missingRequiredEditable;
    }

    private function setMissingRequiredEditable(bool $missingRequiredEditable): void
    {
        $this->missingRequiredEditable = $missingRequiredEditable;
    }

    public function isStaticGeneratorEnabled(): bool
    {
        return $this->staticGeneratorEnabled;
    }

    public function getStaticGeneratorLifetime(): ?int
    {
        return $this->staticGeneratorLifetime;
    }

    private function setController(?string $controller): void
    {
        $this->controller = $controller;
    }

    private function setTemplate(?string $template): void
    {
        $this->template = $template;
    }

    private function setContentMainDocumentId(?int $contentMainDocumentId): void
    {
        $this->contentMainDocumentId = $contentMainDocumentId;
    }

    private function setSupportsContentMain(bool $supportsContentMain): void
    {
        $this->supportsContentMain = $supportsContentMain;
    }

    private function setStaticGeneratorEnabled(bool $staticGeneratorEnabled): void
    {
        $this->staticGeneratorEnabled = $staticGeneratorEnabled;
    }

    private function setStaticGeneratorLifetime(?int $staticGeneratorLifetime): void
    {
        $this->staticGeneratorLifetime = $staticGeneratorLifetime;
    }
}
