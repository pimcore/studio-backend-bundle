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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mercure\Provider;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\UserTopicProvider;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;

final class UserTopicProviderTest extends Unit
{
    public function testGetClientSubscribableTopicWhenLoggedIn(): void
    {
        $userId = 42;
        $userMock = $this->makeEmpty(UserInterface::class, [
            'getId' => $userId,
        ]);

        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class, [
            'isLoggedIn' => true,
            'getCurrentUser' => $userMock,
        ]);

        $userTopicService = new UserTopicService();

        $provider = new UserTopicProvider($securityServiceMock, $userTopicService);

        $topics = $provider->getClientSubscribableTopic();

        $this->assertCount(1, $topics);
        $this->assertSame('studio-backend-default/user/42', $topics[0]);
    }

    public function testGetClientSubscribableTopicWhenNotLoggedIn(): void
    {
        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class, [
            'isLoggedIn' => false,
        ]);

        $userTopicService = new UserTopicService();

        $provider = new UserTopicProvider($securityServiceMock, $userTopicService);

        $topics = $provider->getClientSubscribableTopic();

        $this->assertCount(0, $topics);
    }

    public function testGetServerPublishableTopic(): void
    {
        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class);

        $userTopicService = new UserTopicService();

        $provider = new UserTopicProvider($securityServiceMock, $userTopicService);

        $topics = $provider->getServerPublishableTopic();

        $this->assertCount(1, $topics);
        $this->assertSame('*', $topics[0]);
    }

    public function testGetClientPublishableTopicReturnsEmptyArray(): void
    {
        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class);

        $userTopicService = new UserTopicService();

        $provider = new UserTopicProvider($securityServiceMock, $userTopicService);

        $topics = $provider->getClientPublishableTopic();

        $this->assertCount(0, $topics);
    }
}
