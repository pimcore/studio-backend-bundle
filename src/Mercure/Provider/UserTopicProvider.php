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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Provider;

use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TaggedIteratorAdapter;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorAdapter::TOPIC_LOADER_TAG)]
final class UserTopicProvider extends AbstractServerToClientProvider
{
    public function __construct(
        private readonly SecurityServiceInterface $securityService,
        private readonly UserTopicServiceInterface $userTopicService
    ) {
    }

    public function getClientSubscribableTopic(): array
    {
        if (!$this->securityService->isLoggedIn()) {
            return [];
        }

        $user = $this->securityService->getCurrentUser();

        return [$this->userTopicService->getUserTopic($user->getId())];
    }

    public function getServerPublishableTopic(): array
    {
        return [$this->userTopicService->getWildcardTopic()];
    }
}
