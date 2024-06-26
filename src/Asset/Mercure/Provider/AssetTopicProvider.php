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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Mercure\Provider;

use Pimcore\Bundle\StudioBackendBundle\Asset\Mercure\Events as AssetEvents;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\AbstractServerToClientProvider;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Events;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.mercure.topic.provider')]
final class AssetTopicProvider extends AbstractServerToClientProvider
{
    public function getClientSubscribableTopic(): array
    {
        return $this->getEvents();
    }

    public function getServerPublishableTopic(): array
    {
        return $this->getEvents();
    }

    private function getEvents(): array
    {
        return array_merge(Events::values(), AssetEvents::values());
    }
}
