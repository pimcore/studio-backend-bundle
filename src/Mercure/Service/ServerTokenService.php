<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

use Pimcore\Bundle\StudioBackendBundle\Mercure\Model\TopicCollection;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TopicLoaderInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

/**
 * @internal
 */
final readonly class ServerTokenService implements TokenProviderInterface
{
    public function __construct(
        private TopicLoaderInterface $topicLoader,
        private TokenFactoryInterface $tokenFactory
    ) {
    }

    private function getTopicCollection(): TopicCollection
    {
        return $this->topicLoader->loadTopics();
    }

    public function getJwt(): string
    {
        return $this->tokenFactory->create(
            $this->getTopicCollection()->getServerSubscribableTopics(),
            $this->getTopicCollection()->getServerPublishableTopics(),
        );
    }
}
