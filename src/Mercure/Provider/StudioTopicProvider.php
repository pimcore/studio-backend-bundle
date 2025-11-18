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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Provider;

use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TaggedIteratorAdapter;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Topics;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorAdapter::TOPIC_LOADER_TAG)]
final class StudioTopicProvider extends AbstractServerToClientProvider
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
        return Topics::values();
    }
}
