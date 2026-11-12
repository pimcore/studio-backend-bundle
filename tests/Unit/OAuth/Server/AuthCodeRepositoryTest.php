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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Server;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AuthCodeEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\AuthCodeRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;

final class AuthCodeRepositoryTest extends Unit
{
    public function testGetNewAuthCodeReturnsEntity(): void
    {
        $repo = new AuthCodeRepository($this->makeEmpty(TokenRecordStoreInterface::class));
        $this->assertInstanceOf(AuthCodeEntity::class, $repo->getNewAuthCode());
    }

    public function testRevocationDelegatesToStore(): void
    {
        $repo = new AuthCodeRepository(
            $this->makeEmpty(TokenRecordStoreInterface::class, ['isRevoked' => true]),
        );
        $this->assertTrue($repo->isAuthCodeRevoked('code-1'));
    }
}
