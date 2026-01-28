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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mercure\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicService;

final class UserTopicServiceTest extends Unit
{
    public function testGetUserTopic(): void
    {
        $service = new UserTopicService();

        $this->assertSame('studio/user/1', $service->getUserTopic(1));
        $this->assertSame('studio/user/42', $service->getUserTopic(42));
        $this->assertSame('studio/user/999', $service->getUserTopic(999));
    }

    public function testGetWildcardTopic(): void
    {
        $service = new UserTopicService();

        $this->assertSame('studio/user/*', $service->getWildcardTopic());
    }
}
