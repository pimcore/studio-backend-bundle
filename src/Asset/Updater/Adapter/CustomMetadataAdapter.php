<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Updater\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreSet\CustomMetadataEvent;
use Pimcore\Bundle\StudioBackendBundle\Updater\Adapter\UpdateAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.update_adapter')]
final readonly class CustomMetadataAdapter implements UpdateAdapterInterface
{
    private const INDEX_KEY = 'metadata';

    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function update(ElementInterface $element, array $data): void
    {
        if (!$element instanceof Asset || !array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        $metadataEvent = new CustomMetadataEvent($element->getId(), $data[$this->getIndexKey()]);

        $this->eventDispatcher->dispatch($metadataEvent, CustomMetadataEvent::EVENT_NAME);

        $element->setMetadata($metadataEvent->getCustomMetadata());
    }

    public function getIndexKey(): string
    {
        return self::INDEX_KEY;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }
}
