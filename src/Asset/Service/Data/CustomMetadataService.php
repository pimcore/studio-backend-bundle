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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\Data;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse\CustomMetadataEvent;
use Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator\CustomMetadataHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class CustomMetadataService implements CustomMetadataServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private CustomMetadataHydratorInterface $hydrator,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @return array<int, CustomMetadata>
     *
     * @throws AccessDeniedException
     *
     */
    public function getCustomMetadata(int $id): array
    {
        /** @var Asset $asset */
        $asset = $this->getElement($this->serviceResolver, ElementTypes::TYPE_ASSET, $id);

        $this->securityService->hasElementPermission(
            $asset,
            $this->securityService->getCurrentUser(),
            ElementPermissions::VIEW_PERMISSION
        );

        $customMetadata = [];

        $originalCustomMetadata = $asset->getMetadata();

        if (empty($originalCustomMetadata)) {
            foreach (self::DEFAULT_METADATA as $metadata) {
                $originalCustomMetadata[] = [
                    'name' => $metadata,
                    'language' => '',
                    'type' => 'input',
                    'data' => null,
                ];
            }
        }

        foreach ($originalCustomMetadata as $metadata) {
            $metadata = $this->hydrator->hydrate($metadata);

            $this->eventDispatcher->dispatch(
                new CustomMetadataEvent($metadata),
                CustomMetadataEvent::EVENT_NAME
            );

            $customMetadata[] = $metadata;
        }

        return $customMetadata;
    }
}
