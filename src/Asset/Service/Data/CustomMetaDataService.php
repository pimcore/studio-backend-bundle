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
use Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse\CustomMetaDataEvent;
use Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator\CustomMetaDataHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomMetaData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Asset;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class CustomMetaDataService implements CustomMetaDataServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private CustomMetaDataHydratorInterface $hydrator,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @return array<int, CustomMetaData>
     *
     *@throws AccessDeniedException
     *
     */
    public function getCustomMetaData(int $id): array
    {
        /** @var Asset $asset */
        $asset = $this->getElement($this->serviceResolver, ElementTypes::TYPE_ASSET, $id);

        $this->securityService->hasElementPermission(
            $asset,
            $this->securityService->getCurrentUser(),
            ElementPermissions::VIEW_PERMISSION
        );

        $customMetadata = [];

        $originalCustomMetaData = $asset->getMetadata();

        foreach ($originalCustomMetaData as $metaData) {
            $metaData = $this->hydrator->hydrate($metaData);

            $this->eventDispatcher->dispatch(
                new CustomMetaDataEvent($metaData),
                CustomMetaDataEvent::EVENT_NAME
            );

            $customMetadata[] = $metaData;
        }

        return $customMetadata;
    }
}
