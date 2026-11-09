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
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Grants;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

/**
 * @internal
 */
final readonly class ClientTokenService implements TokenProviderInterface
{
    public function __construct(
        private TopicLoaderInterface $topicLoader,
        private TokenFactoryInterface $tokenFactory,
        private UrlServiceInterface $urlService,
        private SecurityServiceInterface $securityService,
        private string $issuer,
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
            Grants::fromTopics($topics->getClientSubscribableTopics(), $topics->getClientPublishableTopics()),
            [
                'iss' => $this->issuer,
                'aud' => $this->urlService->getSubscriberAudience(),
                'sub' => $this->getSubject(),
                'client_id' => Mercure::CLIENT_ID->value,
                // Without an explicit claim the factory derives `exp` from `session.cookie_lifetime`
                // (or 3600), which has nothing to do with the lifetime the cookie is stamped with and
                // the client is told to renew on. Configuring a longer `cookie_lifetime` would then
                // leave a window where the browser still sends a cookie the hub already rejects, which
                // is the dead-authorization state this whole mechanism exists to avoid.
                'exp' => new DateTimeImmutable('+' . $this->cookieLifetime . ' seconds'),
            ]
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
