<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Provider;

interface ServerTopicProviderInterface
{
    /**
     * @return array<string>
     */
    public function getServerPublishableTopic(): array;

    /**
     * @return array<string>
     */
    public function getServerSubscribableTopic(): array;
}
