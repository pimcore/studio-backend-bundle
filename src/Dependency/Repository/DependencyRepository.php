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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Repository;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;

/**
 * @internal
 */
final readonly class DependencyRepository implements DependencyRepositoryInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    public function listRequiresDependencies(string $elementType, int $elementId): array
    {
        return $this->getElement(
            $this->serviceResolver,
            $elementType,
            $elementId
        )
            ->getDependencies()
            ->getRequires();
    }

    public function listRequiresDependenciesTotalCount(string $elementType, int $elementId): int
    {
        return
            $this->getElement(
                $this->serviceResolver,
                $elementType,
                $elementId
            )
                ->getDependencies()
                ->getRequiresTotalCount();
    }

    public function listRequiredByDependencies(string $elementType, int $elementId): array
    {
        return $this->getElement(
            $this->serviceResolver,
            $elementType,
            $elementId
        )
            ->getDependencies()
            ->getRequiredBy();
    }

    public function listRequiredByDependenciesTotalCount(string $elementType, int $elementId): int
    {
        return
            $this->getElement(
                $this->serviceResolver,
                $elementType,
                $elementId
            )
                ->getDependencies()
                ->getRequiredByTotalCount();
    }
}
