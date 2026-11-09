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

use Pimcore\Bundle\StudioBackendBundle\Mercure\Model\TopicCollection;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TopicLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant\Mercure;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Grants;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

/**
 * @internal
 */
final readonly class ServerTokenService implements TokenProviderInterface
{
    public function __construct(
        private TopicLoaderInterface $topicLoader,
        private TokenFactoryInterface $tokenFactory,
        private UrlServiceInterface $urlService,
        private string $issuer
    ) {
    }

    public function getJwt(): string
    {
        $topics = $this->getTopicCollection();

        return $this->tokenFactory->create(
            Grants::fromTopics($topics->getServerSubscribableTopics(), $topics->getServerPublishableTopics()),
            [
                'iss' => $this->issuer,
                'aud' => $this->urlService->getResourceIdentifier(),
                'sub' => Mercure::CLIENT_ID->value,
                'client_id' => Mercure::CLIENT_ID->value,
            ]
        );
    }

    private function getTopicCollection(): TopicCollection
    {
        return $this->topicLoader->loadTopics();
    }
}
