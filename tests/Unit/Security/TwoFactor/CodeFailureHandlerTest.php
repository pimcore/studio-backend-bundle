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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\TwoFactor;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\CodeFailureHandler;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

final class CodeFailureHandlerTest extends Unit
{
    public function testWrongCodeIsUnauthorized(): void
    {
        $response = (new CodeFailureHandler())->onAuthenticationFailure(
            Request::create('/login/2fa', 'POST'),
            new InvalidTwoFactorCodeException('Invalid two-factor authentication code.')
        );

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame(['message' => 'Invalid code.'], $this->json($response));
    }

    public function testTooManyAttemptsIsTooManyRequests(): void
    {
        $response = (new CodeFailureHandler())->onAuthenticationFailure(
            Request::create('/login/2fa', 'POST'),
            new TooManyLoginAttemptsAuthenticationException(5)
        );

        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertSame(['message' => 'Too many attempts, please try again later.'], $this->json($response));
    }

    /**
     * @return array<string, mixed>
     */
    private function json(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
