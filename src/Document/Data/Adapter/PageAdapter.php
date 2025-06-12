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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Data\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\SettingsDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Settings\PageSettingsData;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(AdapterLoader::DOCUMENT_TYPE_ADAPTER_TAG->value)]
final readonly class PageAdapter extends AbstractPageSnippetAdapter
{
    public function normalizeSettings(Document $document): ?SettingsDataInterface
    {
        if (!$document instanceof Page) {
            return null;
        }

        return new PageSettingsData(
            $document->getTitle(),
            $document->getDescription(),
            $document->getPrettyUrl(),
            ...$this->getBasePageSnippetData($document)
        );
    }
}
