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

use Pimcore\Bundle\StudioBackendBundle\Dependency\Extractor\DependencyDataExtractorInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Schema\Dependency;

/**
 * @internal
 */
final readonly class DependencyHydrator implements DependencyHydratorInterface
{
    public function __construct(
        private DependencyDataExtractorInterface $dataExtractor
    ) {
    }

    public function hydrate(array $dependency): ?Dependency
    {
        $elementId = $dependency['id'] ?? null;
        $elementType = $dependency['type'] ?? null;

        if($elementId === null || $elementType === null) {
            return null;
        }

        $data = $this->dataExtractor->extractData($elementType, $elementId);

        return new Dependency(
            $data['id'],
            $data['path'],
            $data['type'],
            $data['subtype'],
            $data['published']
        );
    }
}