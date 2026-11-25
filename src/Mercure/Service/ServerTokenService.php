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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

use DateTimeImmutable;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Model\TopicCollection;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TopicLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant\Mercure;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

/**
 * @internal
 */
final readonly class ServerTokenService implements TokenProviderInterface
{
    private const int LIFETIME = 3600;

    public function __construct(
        private TopicLoaderInterface $topicLoader,
        private AccessTokenFactoryInterface $tokenFactory,
        private AudienceServiceInterface $audienceService
    ) {
    }

    public function getJwt(): string
    {
        $topics = $this->getTopicCollection();

        // Minted per publish, see PublishService
        return $this->tokenFactory->create(
            $topics->getServerSubscribableTopics(),
            $topics->getServerPublishableTopics(),
            Mercure::CLIENT_ID->value,
            [$this->audienceService->getResourceIdentifier()],
            new DateTimeImmutable('+' . self::LIFETIME . ' seconds')
        );
    }

    private function getTopicCollection(): TopicCollection
    {
        return $this->topicLoader->loadTopics();
    }
}
