<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Model;

/**
 * @internal
 */
final class TopicCollection
{
    public function __construct(
        private array $serverPublishableTopics = [],
        private array $serverSubscribableTopics = [],
        private array $clientPublishableTopics = [],
        private array $clientSubscribableTopics = []
    ) {
    }

    /**
     * @param string|array<string> $topics
     * @return void
     */
    public function addServerPublishableTopic(string|array $topics): void
    {
        if (is_array($topics)) {
            $this->serverPublishableTopics = array_merge($this->serverPublishableTopics, $topics);
            return;
        }

        $this->serverPublishableTopics[] = $topics;
    }

    /**
     * @param string|array<string> $topics
     * @return void
     */
    public function addServerSubscribableTopic(string|array $topics): void
    {
        if (is_array($topics)) {
            $this->serverSubscribableTopics = array_merge($this->serverSubscribableTopics, $topics);
            return;
        }

        $this->serverSubscribableTopics[] = $topics;
    }

    /**
     * @param string|array<string> $topics
     * @return void
     */
    public function addClientPublishableTopic(string|array $topics): void
    {
        if (is_array($topics)) {
            $this->clientPublishableTopics = array_merge($this->clientPublishableTopics, $topics);
            return;
        }

        $this->clientPublishableTopics[] = $topics;
    }

    /**
     * @param string|array<string> $topics
     * @return void
     */
    public function addClientSubscribableTopic(string|array $topics): void
    {
        if (is_array($topics)) {
            $this->clientSubscribableTopics = array_merge($this->clientSubscribableTopics, $topics);
            return;
        }

        $this->clientSubscribableTopics[] = $topics;
    }

    /**
     * @return array<string>
     */
    public function getServerPublishableTopics(): array
    {
        return array_unique($this->serverPublishableTopics);
    }

    /**
     * @return array<string>
     */
    public function getServerSubscribableTopics(): array
    {
        return array_unique($this->serverSubscribableTopics);
    }

    /**
     * @return array<string>
     */
    public function getClientPublishableTopics(): array
    {
        return array_unique($this->clientPublishableTopics);
    }

    /**
     * @return array<string>
     */
    public function getClientSubscribableTopics(): array
    {
        return array_unique($this->clientSubscribableTopics);
    }
}