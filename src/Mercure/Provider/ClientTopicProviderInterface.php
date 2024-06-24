<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Provider;

interface ClientTopicProviderInterface
{
    /**
     * @return array<string>
     */
    public function getClientPublishableTopic(): array;

    /**
     * @return array<string>
     */
    public function getClientSubscribableTopic(): array;
}
