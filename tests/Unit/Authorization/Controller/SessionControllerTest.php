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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Authorization\Controller;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Controller\SessionController;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\SessionStatus;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Service\SessionStatusServiceInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The session status route answers 200 for every request, so the login page can ask it without
 * provoking a 401.
 *
 * @internal
 */
final class SessionControllerTest extends Unit
{
    public function testAnswersTheStatusWith200(): void
    {
        $response = $this->createController(new SessionStatus(false))->getSessionStatus();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"authenticated":false}', $response->getContent());
    }

    public function testResponseIsNotCacheable(): void
    {
        $response = $this->createController(new SessionStatus(true))->getSessionStatus();

        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
    }

    private function createController(SessionStatus $status): SessionController
    {
        $serializer = $this->makeEmpty(SerializerInterface::class, [
            'serialize' => static fn (SessionStatus $data): string => json_encode(['authenticated' => $data->isAuthenticated()]),
        ]);
        $service = $this->makeEmpty(SessionStatusServiceInterface::class, [
            'getSessionStatus' => $status,
        ]);

        return new SessionController($serializer, $service);
    }
}
