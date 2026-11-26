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
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

/**
 * @internal
 */
final readonly class ClientTokenService implements TokenProviderInterface
{
    public function __construct(
        private TopicLoaderInterface $topicLoader,
        private AccessTokenFactoryInterface $tokenFactory,
        private AudienceServiceInterface $audienceService,
        private SecurityServiceInterface $securityService,
        private int $cookieLifetime = 3600
    ) {
    }

    private function getTopicCollection(): TopicCollection
    {
        return $this->topicLoader->loadTopics();
    }

    public function getJwt(): string
    {
        $topics = $this->getTopicCollection();

        return $this->tokenFactory->create(
            $topics->getClientSubscribableTopics(),
            $topics->getClientPublishableTopics(),
            $this->getSubject(),
            $this->audienceService->getSubscriberAudience(),
            // The token must expire with the cookie it is stamped into and the client renews on.
            // A token outliving the cookie is harmless; one expiring earlier leaves the browser
            // sending a cookie the hub already rejects, the dead-authorization state this whole
            // mechanism exists to avoid.
            new DateTimeImmutable('+' . $this->cookieLifetime . ' seconds')
        );
    }

    private function getSubject(): string
    {
        if (!$this->securityService->isLoggedIn()) {
            return Mercure::CLIENT_ID->value;
        }

        return (string)$this->securityService->getCurrentUser()->getId();
    }
}
