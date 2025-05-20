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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Updater\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Metadata\Event\PreSet\CustomMetadataEvent;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\DataResolverServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Updater\Adapter\UpdateAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_key_exists;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.update_adapter')]
final readonly class CustomMetadataAdapter implements UpdateAdapterInterface
{
    private const string INDEX_KEY = 'metadata';

    public function __construct(
        private DataResolverServiceInterface $dataResolverService,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
    ) {
    }

    public function update(ElementInterface $element, array $data): void
    {
        if (!$element instanceof Asset || !array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        $metadataEvent = new CustomMetadataEvent(
            $element->getId(),
            $this->denormalizeMetadata($data[$this->getIndexKey()])
        );

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

    private function denormalizeMetadata(array $customMetadata): array
    {
        $user = $this->securityService->getCurrentUser();

        return array_map(function ($metadataItem) use ($user) {
            $metadataItem['data'] = $this->dataResolverService->denormalizeData(
                $metadataItem,
                $user,
                $metadataItem['type']
            );

            return $metadataItem;
        }, $customMetadata);
    }
}
