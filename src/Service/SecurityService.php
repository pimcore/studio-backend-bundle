<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Tool\TmpStoreResolverInterface;

/**
 * @internal
 */
final readonly class SecurityService implements SecurityServiceInterface
{
    public function __construct(private TmpStoreResolverInterface $tmpStoreResolver)
    {
    }

    public function isAllowed(string $token): bool
    {
        $userIds = $this->tmpStoreResolver->getIdsByTag($token);
        if(count($userIds) === 0 || count($userIds) > 1) {
            return false;
        }
        $entry = $this->tmpStoreResolver->get($userIds[0]);
        return $entry && $entry->getTag() === $token;
    }
}