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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\PreviewConfigEvent;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\PreviewConfigHydratorInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\PreviewGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class PreviewConfigService implements PreviewConfigServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private PreviewConfigHydratorInterface $previewConfigHydrator,
        private PreviewGeneratorInterface $defaultPreviewGenerator,
    ) {
    }

    public function getPreviewConfig(Concrete $dataObject, ClassDefinition $class): ?array
    {
        $previewGenerator = $class->getPreviewGenerator();

        if (!$previewGenerator && $class->getLinkGenerator()) {
            $previewGenerator = $this->defaultPreviewGenerator;
        }

        if (!$previewGenerator) {
            return null;
        }

        $rawConfig = $previewGenerator->getPreviewConfig($dataObject);

        if (empty($rawConfig)) {
            return null;
        }

        $previewConfig = [];
        foreach ($rawConfig as $rawEntry) {
            $entry = $this->previewConfigHydrator->hydratePreviewConfigEntry($rawEntry);
            $this->eventDispatcher->dispatch(
                new PreviewConfigEvent($entry),
                PreviewConfigEvent::EVENT_NAME,
            );
            $previewConfig[] = $entry;
        }

        return $previewConfig;
    }
}
