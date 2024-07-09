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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Mercure\Provider;

use Pimcore\Bundle\StudioBackendBundle\Element\Mercure\Events;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\AbstractServerToClientProvider;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TaggedIteratorAdapter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorAdapter::TOPIC_LOADER_TAG)]
final class ElementTopicProvider extends AbstractServerToClientProvider
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
        return Events::values();
    }
}
