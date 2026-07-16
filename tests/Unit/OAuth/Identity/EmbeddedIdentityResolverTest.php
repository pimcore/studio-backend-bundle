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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Identity;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Identity\EmbeddedIdentityResolver;
use Pimcore\Model\User;

final class EmbeddedIdentityResolverTest extends Unit
{
    public function testResolvesValidNumericSubjectToUser(): void
    {
        // Pimcore\Model\User is final; the repo pattern (see McpAccessTokenServiceTest)
        // is to construct a real, unsaved instance rather than a mock.
        $user = new User();
        $authResolver = $this->createMock(AuthenticationResolverInterface::class);
        $authResolver->method('isValidUser')->willReturn(true);

        $resolver = new EmbeddedIdentityResolver($authResolver, static fn (int $id): ?User => $user);

        $this->assertSame($user, $resolver->resolve('42'));
    }

    public function testRejectsDisabledOrInvalidUser(): void
    {
        $user = new User();
        $authResolver = $this->createMock(AuthenticationResolverInterface::class);
        $authResolver->method('isValidUser')->willReturn(false);

        $resolver = new EmbeddedIdentityResolver($authResolver, static fn (int $id): ?User => $user);

        $this->assertNull($resolver->resolve('42'));
    }

    public function testRejectsNonNumericSubject(): void
    {
        $authResolver = $this->createMock(AuthenticationResolverInterface::class);
        // A non-numeric subject must be rejected before any user lookup happens.
        $authResolver->expects($this->never())->method('isValidUser');

        $resolver = new EmbeddedIdentityResolver(
            $authResolver,
            static fn (int $id): ?User => throw new \LogicException('loader must not be called')
        );

        $this->assertNull($resolver->resolve('admin'));
    }
}
