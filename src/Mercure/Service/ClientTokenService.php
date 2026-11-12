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
        private int $cookieLifetime = 3600
    ) {
    }

    private function getTopicCollection(): TopicCollection
    {
        return $this->topicLoader->loadTopics();
    }

    public function getJwt(): string
    {
        return $this->tokenFactory->create(
            $this->getTopicCollection()->getClientSubscribableTopics(),
            $this->getTopicCollection()->getClientPublishableTopics(),
            // Without an explicit claim the factory derives `exp` from `session.cookie_lifetime`
            // (or 3600), which has nothing to do with the lifetime the cookie is stamped with and
            // the client is told to renew on. Configuring a longer `cookie_lifetime` would then
            // leave a window where the browser still sends a cookie the hub already rejects, which
            // is the dead-authorization state this whole mechanism exists to avoid.
            ['exp' => new DateTimeImmutable('+' . $this->cookieLifetime . ' seconds')]
        );
    }
}
