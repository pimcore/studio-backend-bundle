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
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\AuthenticationRequiredHandler;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\SetupProvider;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class AuthenticationRequiredHandlerTest extends Unit
{
    /**
     * The API is called from the Studio UI, so a pending code is a JSON 401, never scheb's
     * redirect to an HTML form.
     */
    public function testPendingCodeIsAJsonUnauthorizedWithoutUserData(): void
    {
        $token = $this->makeEmpty(TwoFactorTokenInterface::class, ['getUserIdentifier' => 'john']);

        $response = (new AuthenticationRequiredHandler())->onAuthenticationRequired(
            Request::create('/pimcore-studio/api/user/current-user-information'),
            $token
        );

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame(
            [
                'message' => 'Two-factor authentication is required.',
                'twoFactorRequired' => true,
                'twoFactorStep' => 'verify',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * After an SSO redirect the UI learns the step only from this 401, so it carries the setup step too.
     */
    public function testPendingSetupNamesTheSetupStep(): void
    {
        $token = $this->makeEmpty(TwoFactorTokenInterface::class, [
            'getCurrentTwoFactorProvider' => SetupProvider::ALIAS,
        ]);

        $response = (new AuthenticationRequiredHandler())->onAuthenticationRequired(
            Request::create('/pimcore-studio/api/user/current-user-information'),
            $token
        );

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame(
            [
                'message' => 'Two-factor authentication is required.',
                'twoFactorRequired' => true,
                'twoFactorStep' => 'setup',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Without a pending two-factor login there is no step to name.
     */
    public function testOtherTokenGetsTheMessageOnly(): void
    {
        $token = new UsernamePasswordToken(new InMemoryUser('john', null), 'pimcore_studio');

        $response = (new AuthenticationRequiredHandler())->onAuthenticationRequired(
            Request::create('/pimcore-studio/api/user/current-user-information'),
            $token
        );

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame(
            ['message' => 'Two-factor authentication is required.'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }
}
