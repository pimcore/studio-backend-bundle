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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Event\CustomSettingsEvent;
use Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator\CustomSettingsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSettings;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Asset;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class CustomSettingsService implements CustomSettingsServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private CustomSettingsHydratorInterface $hydrator,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }
    public function getCustomSettings(int $id): CustomSettings
    {
        /** @var Asset $asset */
        $asset = $this->getElement($this->serviceResolver, ElementTypes::TYPE_ASSET, $id);
        $this->securityService->hasElementPermission(
            $asset,
            $this->securityService->getCurrentUser(),
            ElementPermissions::VIEW_PERMISSION
        );
        $customSettings = $this->hydrator->hydrate($asset->getCustomSettings());

        $this->eventDispatcher->dispatch(
            new CustomSettingsEvent($customSettings),
            CustomSettingsEvent::EVENT_NAME
        );

        return $customSettings;
    }
}