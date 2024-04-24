<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Authorization\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Tool\TmpStoreResolverInterface;
use Pimcore\Bundle\StudioApiBundle\Authorization\Info;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

/**
 * @internal
 */
final class TokenService implements TokenServiceInterface
{
    private const TMP_STORE_TAG = 'studio-api-token-tag-user-{userId}';

    private const TMP_STORE_TAG_PLACEHOLDER = '{userId}';

    public function __construct(
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly TmpStoreResolverInterface $tmpStoreResolver,
        private readonly int $tokenLifetime,
    ) {
    }

    public function generateAndSaveToken(string $userIdentifier): string
    {
        do {
            $token = $this->tokenGenerator->generateToken();
            $entry = $this->tmpStoreResolver->get($token);
        } while ($entry !== null);

        $this->saveToken(new Info($token, $userIdentifier));

        return $token;
    }

    public function refreshToken(string $token): Info
    {
        $entry = $this->tmpStoreResolver->get($token);
        if($entry === null) {
            throw new TokenNotFoundException('Token not found');
        }

        $data = $entry->getData();

        if(!isset($data['username'])) {
            throw new TokenNotFoundException('Token not found');
        }

        $tokenInfo = new Info($token, $data['username']);
        $this->saveToken($tokenInfo);

        return $tokenInfo;
    }

    public function getLifetime(): int
    {
        return $this->tokenLifetime;
    }

    private function saveToken(Info $tokenInfo): void
    {
        $this->tmpStoreResolver->set(
            $tokenInfo->getToken(),
            [
                'username' => $tokenInfo->getUsername(),
            ],
            $this->getTmpStoreTag($tokenInfo->getUsername()),
            $this->tokenLifetime
        );
    }

    private function getTmpStoreTag(string $userId): string
    {
        return str_replace(
            self::TMP_STORE_TAG_PLACEHOLDER,
            $userId,
            self::TMP_STORE_TAG
        );
    }
}
