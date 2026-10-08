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
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\LoginSuccessHandler;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\SetupProvider;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class LoginSuccessHandlerTest extends Unit
{
    /**
     * The UI tells "code needed" from "logged in" by these two fields in a successful login.
     */
    public function testPasswordAcceptedButCodePendingAnswersTheStepWithoutUserData(): void
    {
        $token = $this->makeEmpty(TwoFactorTokenInterface::class, ['getUserIdentifier' => 'john']);

        $response = (new LoginSuccessHandler())->onAuthenticationSuccess(Request::create('/login'), $token);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            ['twoFactorRequired' => true, 'twoFactorStep' => 'verify'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * A user who must use two-factor authentication but has not set it up is sent to the setup.
     */
    public function testLoginWaitingForTheFirstSetupAnswersTheSetupStep(): void
    {
        $token = $this->makeEmpty(TwoFactorTokenInterface::class, [
            'getCurrentTwoFactorProvider' => SetupProvider::ALIAS,
        ]);

        $response = (new LoginSuccessHandler())->onAuthenticationSuccess(Request::create('/login'), $token);

        $this->assertSame(
            ['twoFactorRequired' => true, 'twoFactorStep' => 'setup'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Same answer as before for a completed login: an empty 200.
     */
    public function testCompletedLoginAnswersAnEmptyOk(): void
    {
        $token = new UsernamePasswordToken(new InMemoryUser('john', null), 'pimcore_studio');

        $response = (new LoginSuccessHandler())->onAuthenticationSuccess(Request::create('/login'), $token);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }
}
