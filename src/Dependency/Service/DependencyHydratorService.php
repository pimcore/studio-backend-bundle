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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolver;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator\DependencyHydrator;
use Pimcore\Bundle\StudioBackendBundle\Dependency\RepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;

final readonly class DependencyHydratorService implements DependencyHydratorServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private RepositoryInterface $repository,
        private ServiceResolver $serviceResolver,
        private DependencyHydrator $dependencyHydrator,
    ) {
    }

    public function getHydratedDependenciesForElement(string $elementType, int $elementId): array
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $elementId);

        $hydratedProperties = [];

        foreach($element->getDependencies() as $dependency) {
            $hydratedProperties[] = $this->dependencyHydrator->hydrate($dependency);
        }

        return ['items' => $hydratedProperties];
    }
}