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
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\SettingsDataInterface;

/**
 * @internal
 */
trait DocumentSettingsDataTrait
{
    #[Property(
        description: 'Document Settings Data',
        type: 'object',
        example: [
            'title' => 'Some Title',
            'description' => 'Some Description',
            'prettyUrl' => 'pretty/url',
            'controller' => 'App\\Controller\\PageController',
            'template' => '@app/template.html.twig',
            'contentMainDocumentId' => 123,
            'contentMainDocumentPath' => '/path/to/main/document',
            'supportsContentMain' => false,
            'missingRequiredEditable' => false,
            'staticGeneratorEnabled' => false,
            'staticGeneratorLifetime' => 123456,
            'staticLastGenerated' => 1700000000,
            'url' => 'https://example.com/',
        ]
    )]
    private ?SettingsDataInterface $settingsData = null;

    public function getSettingsData(): ?SettingsDataInterface
    {
        return $this->settingsData;
    }

    public function setSettingsData(?SettingsDataInterface $settingsData = null): void
    {
        $this->settingsData = $settingsData;
    }
}
