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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Repository;

use Pimcore\Bundle\StaticResolverBundle\Models\Metadata\Predefined\PredefinedResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\MappedParameter\MetadataParameters;
use Pimcore\Model\Metadata\Predefined;
use Pimcore\Model\Metadata\Predefined\Listing;

/**
 * @internal
 */
final readonly class MetadataRepository implements MetadataRepositoryInterface
{
    public function __construct(private PredefinedResolverInterface $predefinedResolver)
    {
    }

    /**
     * @return Predefined[]
     */
    public function getAllPredefinedMetadata(): array
    {
        return (new Listing())->load();
    }

    public function getAllPredefinedMetadataDefinitions(MetadataParameters $metadataParameters): array
    {
        $listing = new Listing();
        $filter = $metadataParameters->getFilter();
        if ($filter !== null) {
            $listing->setFilter(function (Predefined $predefined) use ($filter) {
                foreach ($predefined->getObjectVars() as $value) {
                    if (stripos((string)$value, $filter) !== false) {
                        return true;
                    }
                }

                return false;
            });
        }

        return $listing->getDefinitions();
    }

    public function getPredefinedMetadataByName(string $name): ?Predefined
    {
        return $this->predefinedResolver->getByName($name);
    }
}
