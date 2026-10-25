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
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\SetupProvider;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The session status route answers 200 for every request, so the login page can ask it without
 * provoking a 401.
 *
 * @internal
 */
final class SessionControllerTest extends Unit
{
    public function testAnswersTrueForAStudioUser(): void
    {
        $response = $this->createController(true)->getSessionStatus(new TokenStorage(), 6);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"authenticated":true,"twoFactorRequired":null,"twoFactorStep":null,"digits":null}', $response->getContent());
    }

    public function testAnswersFalseWithoutSessionAndStillReturns200(): void
    {
        $response = $this->createController(false)->getSessionStatus(new TokenStorage(), 6);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"authenticated":false,"twoFactorRequired":null,"twoFactorStep":null,"digits":null}', $response->getContent());
    }

    public function testResponseIsNotCacheable(): void
    {
        $response = $this->createController(false)->getSessionStatus(new TokenStorage(), 6);

        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
    }

    public function testPendingCodeIsNotAuthenticatedAndCarriesTheVerifyStep(): void
    {
        $response = $this->createController(false)->getSessionStatus($this->storageWith('google'), 6);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            '{"authenticated":false,"twoFactorRequired":true,"twoFactorStep":"verify","digits":6}',
            $response->getContent()
        );
    }

    public function testPendingSetupCarriesTheSetupStep(): void
    {
        $response = $this->createController(false)->getSessionStatus($this->storageWith(SetupProvider::ALIAS), 8);

        $this->assertSame(
            '{"authenticated":false,"twoFactorRequired":true,"twoFactorStep":"setup","digits":8}',
            $response->getContent()
        );
    }

    private function storageWith(string $provider): TokenStorage
    {
        $token = $this->makeEmpty(TwoFactorTokenInterface::class, [
            'getCurrentTwoFactorProvider' => $provider,
        ]);
        $this->assertInstanceOf(TokenInterface::class, $token);

        $storage = new TokenStorage();
        $storage->setToken($token);

        return $storage;
    }

    private function createController(bool $granted): SessionController
    {
        $serializer = $this->makeEmpty(SerializerInterface::class, [
            'serialize' => static fn (object $data): string => json_encode([
                'authenticated' => $data->isAuthenticated(),
                'twoFactorRequired' => $data->getTwoFactorRequired(),
                'twoFactorStep' => $data->getTwoFactorStep(),
                'digits' => $data->getDigits(),
            ]),
        ]);

        $checker = $this->makeEmpty(AuthorizationCheckerInterface::class, [
            'isGranted' => fn (string $attribute): bool => $attribute === SessionController::ROLE_STUDIO_USER && $granted,
        ]);

        $container = new Container();
        $container->set('security.authorization_checker', $checker);

        $controller = new SessionController($serializer);
        $controller->setContainer($container);

        return $controller;
    }
}
