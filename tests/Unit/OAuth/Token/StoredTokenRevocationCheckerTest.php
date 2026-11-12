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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Token;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\TokenRecordStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Token\StoredTokenRevocationChecker;

final class StoredTokenRevocationCheckerTest extends Unit
{
    public function testDelegatesToStore(): void
    {
        $revoked = new StoredTokenRevocationChecker(
            $this->makeEmpty(TokenRecordStoreInterface::class, ['isRevoked' => true]),
        );
        $this->assertTrue($revoked->isRevoked('jti-1'));

        $active = new StoredTokenRevocationChecker(
            $this->makeEmpty(TokenRecordStoreInterface::class, ['isRevoked' => false]),
        );
        $this->assertFalse($active->isRevoked('jti-2'));
    }
}
