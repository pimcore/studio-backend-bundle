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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthTokenRecord;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\AuthCodeEntity;
use function ctype_digit;

/**
 * Records issued authorization codes so they can be one-time-used and revoked.
 *
 * @internal
 */
final class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    public function __construct(
        private readonly TokenRecordStoreInterface $tokenRecordStore,
    ) {
    }

    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $subject = $authCodeEntity->getUserIdentifier();

        $this->tokenRecordStore->persist(
            $authCodeEntity->getIdentifier(),
            OAuthTokenRecord::TYPE_AUTH_CODE,
            $authCodeEntity->getExpiryDateTime()->getTimestamp(),
            $subject !== null && ctype_digit($subject) ? (int) $subject : null,
            $authCodeEntity->getClient()->getIdentifier(),
        );
    }

    public function revokeAuthCode(string $codeId): void
    {
        $this->tokenRecordStore->revoke($codeId);
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        return $this->tokenRecordStore->isRevoked($codeId);
    }
}
