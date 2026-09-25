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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Mercure\Model\TopicCollection;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\ClientTopicProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\ServerTopicProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements TopicLoaderInterface
{
    public const TOPIC_LOADER_TAG = 'pimcore.studio_backend.mercure.topic.provider';

    public function __construct(
        #[AutowireIterator(self::TOPIC_LOADER_TAG)]
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
