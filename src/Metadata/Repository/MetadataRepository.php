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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Repository;

use Pimcore\Bundle\StaticResolverBundle\Models\Metadata\Predefined\PredefinedResolverInterface;
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

    public function getPredefinedMetadataByName(string $name): ?Predefined
    {
        return $this->predefinedResolver->getByName($name);
    }
}
