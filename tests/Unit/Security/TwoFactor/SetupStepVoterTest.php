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
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\SetupProvider;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\SetupStepVoter;
use Pimcore\Bundle\StudioBackendBundle\User\Controller\TwoFactor\SetupController;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Lets a login that waits for its first setup reach exactly the setup endpoint, through the
 * application's existing access rule for the Studio API.
 */
final class SetupStepVoterTest extends Unit
{
    private const string ROLE = 'ROLE_PIMCORE_USER';

    public function testGrantsTheSetupEndpointToALoginInTheSetupStep(): void
    {
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->vote($this->pending(SetupProvider::ALIAS), $this->setupRequest(), [self::ROLE])
        );
    }

    /**
     * A login waiting for a verify code must never reach the setup: it would replace the secret.
     */
    public function testAbstainsForALoginInTheVerifyStep(): void
    {
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->vote($this->pending('google'), $this->setupRequest(), [self::ROLE])
        );
    }

    public function testAbstainsForEveryOtherRoute(): void
    {
        $request = Request::create('/pimcore-studio/api/user/current-user-information', 'POST');
        $request->attributes->set('_route', 'pimcore_studio_api_current_user');

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->vote($this->pending(SetupProvider::ALIAS), $request, [self::ROLE])
        );
    }

    public function testAbstainsForOtherMethodsOnTheSetupPath(): void
    {
        $request = $this->setupRequest();
        $request->setMethod('GET');

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->vote($this->pending(SetupProvider::ALIAS), $request, [self::ROLE])
        );
    }

    public function testAbstainsForOtherAttributes(): void
    {
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->vote($this->pending(SetupProvider::ALIAS), $this->setupRequest(), ['ROLE_PIMCORE_ADMIN'])
        );
    }

    /**
     * A full login is the role voter's business; this voter never denies anything.
     */
    public function testAbstainsForCompletedLogins(): void
    {
        $token = new UsernamePasswordToken(new InMemoryUser('john', null), 'pimcore_studio');

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote($token, $this->setupRequest(), [self::ROLE]));
    }

    public function testAbstainsWithoutARequestSubject(): void
    {
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->vote($this->pending(SetupProvider::ALIAS), null, [self::ROLE])
        );
    }

    /**
     * @param list<string> $attributes
     */
    private function vote(TokenInterface $token, ?Request $request, array $attributes): int
    {
        return (new SetupStepVoter())->vote($token, $request, $attributes);
    }

    private function pending(string $provider): TwoFactorTokenInterface
    {
        return $this->makeEmpty(TwoFactorTokenInterface::class, ['getCurrentTwoFactorProvider' => $provider]);
    }

    private function setupRequest(): Request
    {
        $request = Request::create('/pimcore-studio/api/user/two-factor/setup', 'POST');
        $request->attributes->set('_route', SetupController::ROUTE_NAME);

        return $request;
    }
}
