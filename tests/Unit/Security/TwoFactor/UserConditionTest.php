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
use Pimcore\Bundle\StudioBackendBundle\Authorization\Controller\TokenLoginController;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\UserCondition;
use Pimcore\Model\User as PimcoreUser;
use Pimcore\Security\User\User;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserConditionTest extends Unit
{
    /**
     * @dataProvider userProvider
     */
    public function testAsksForACodeWhenRequiredOrEnabled(bool $required, bool $enabled, bool $expected): void
    {
        $context = $this->context($this->pimcoreUser($required, $enabled), 'pimcore_studio_api_login');

        $this->assertSame($expected, (new UserCondition())->shouldPerformTwoFactorAuthentication($context));
    }

    /**
     * @return array<string, array{0: bool, 1: bool, 2: bool}>
     */
    public static function userProvider(): array
    {
        return [
            'neither' => [false, false, false],
            'required only' => [true, false, true],
            'enabled only' => [false, true, true],
            'required and enabled' => [true, true, true],
        ];
    }

    /**
     * Token login has never asked for a code, in Classic neither.
     */
    public function testTokenLoginDoesNotAskForACode(): void
    {
        $context = $this->context($this->pimcoreUser(true, true), TokenLoginController::ROUTE_NAME);

        $this->assertFalse((new UserCondition())->shouldPerformTwoFactorAuthentication($context));
    }

    /**
     * Other user types are left to their providers, as without a condition.
     */
    public function testOtherUserTypesAreLeftToTheProviders(): void
    {
        $context = $this->context($this->makeEmpty(UserInterface::class), 'portal_login');

        $this->assertTrue((new UserCondition())->shouldPerformTwoFactorAuthentication($context));
    }

    private function pimcoreUser(bool $required, bool $enabled): User
    {
        $user = new PimcoreUser();
        $user->setTwoFactorAuthentication('required', $required);
        $user->setTwoFactorAuthentication('enabled', $enabled);

        return new User($user);
    }

    private function context(UserInterface $user, string $route): AuthenticationContextInterface
    {
        $request = Request::create('/pimcore-studio/api/login', 'POST');
        $request->attributes->set('_route', $route);

        return $this->makeEmpty(AuthenticationContextInterface::class, [
            'getUser' => $user,
            'getRequest' => $request,
        ]);
    }
}
