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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Schema\Dependency;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;

/**
 * @internal
 */
final readonly class DependencyHydrator implements DependencyHydratorInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ServiceResolverInterface $serviceResolver,
    ) {

    }

    public function hydrate(array $dependency): ?Dependency
    {
        $elementId = $dependency['id'] ?? null;
        $elementType = $dependency['type'] ?? null;

        if($elementId === null || $elementType === null) {
            return null;
        }

        $data = $this->extractData($elementType, $elementId);

        return new Dependency(
            $data['id'],
            $data['path'],
            $data['type'],
            $data['subtype'],
            $data['published']
        );
    }

    private function extractData(string $elementType, int $elementId): array
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $elementId);

        return [
            'id' => $element->getId(),
            'type' => $this->serviceResolver->getElementType($element),
            'subtype' => $element->getType(),
            'published' => $this->serviceResolver->isPublished($element),
            'path' => $element->getRealFullPath(),
        ];
    }
}
