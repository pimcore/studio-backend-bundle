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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service;

use Pimcore\Bundle\StaticResolverBundle\Lib\ConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse\AssetTypeEvent;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetType;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocumentTypeEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentType;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class ElementService implements ElementServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ConfigResolverInterface $configResolver,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getAssetTypes(): array
    {
        $assetTypes = [];
        $assetsConfig = $this->configResolver->getSystemConfiguration('assets');
        if (isset($assetsConfig['type_definitions']['map']) && is_array($assetsConfig['type_definitions']['map'])) {
            foreach ($assetsConfig['type_definitions']['map'] as $key => $definition) {
                $assetType = new AssetType($key);
                $this->eventDispatcher->dispatch(new AssetTypeEvent($assetType), AssetTypeEvent::EVENT_NAME);
                $assetTypes[] = $assetType;
            }
        }

        return $assetTypes;
    }

    public function getDocumentTypes(): array
    {
        $types = [];
        $config = $this->configResolver->getSystemConfiguration('documents');
        if (isset($config['type_definitions']['map']) && is_array($config['type_definitions']['map'])) {
            foreach ($config['type_definitions']['map'] as $key => $definition) {
                $type = new DocumentType($key);
                $this->eventDispatcher->dispatch(new DocumentTypeEvent($type), DocumentTypeEvent::EVENT_NAME);
                $types[] = $type;
            }
        }

        return $types;
    }
}
