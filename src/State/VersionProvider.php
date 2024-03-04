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

namespace Pimcore\Bundle\StudioApiBundle\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Version\VersionResolverInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Version;

final readonly class VersionProvider implements ProviderInterface
{
    public function __construct(
        private VersionResolverInterface $versionResolver,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return null;
        }
        $version = $this->versionResolver->getById($uriVariables['id']);
        if ($version === null) {
            return null;
        }

        return new Version($version);
    }
}
