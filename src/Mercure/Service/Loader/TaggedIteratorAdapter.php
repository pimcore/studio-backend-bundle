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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Mercure\Model\TopicCollection;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\ServerTopicProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\ClientTopicProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements TopicLoaderInterface
{
    public const TOPIC_LOADER_TAG = 'pimcore.studio_backend.mercure.topic.provider';

    public function __construct(
        #[TaggedIterator(self::TOPIC_LOADER_TAG)]
        private readonly iterable $taggedTopicProviders,
    ) {
    }

    public function loadTopics(): TopicCollection
    {
        $collection = new TopicCollection();
        foreach ($this->taggedTopicProviders as $topicProvider) {
            if ($topicProvider instanceof ServerTopicProviderInterface) {
                $collection->addServerPublishableTopic($topicProvider->getServerPublishableTopic());
                $collection->addServerSubscribableTopic($topicProvider->getServerSubscribableTopic());
            }

            if ($topicProvider instanceof ClientTopicProviderInterface) {
                $collection->addClientPublishableTopic($topicProvider->getClientPublishableTopic());
                $collection->addClientSubscribableTopic($topicProvider->getClientSubscribableTopic());
            }
        }
        return $collection;
    }
}
