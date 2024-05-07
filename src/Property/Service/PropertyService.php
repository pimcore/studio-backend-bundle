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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Service;

use Pimcore\Bundle\StudioBackendBundle\Property\RepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Model\Property\Predefined;

/**
 * @internal
 */
final readonly class PropertyService implements PropertyServiceInterface
{
    public function __construct(private RepositoryInterface $repository)
    {

    }

    public function updatePredefinedProperty(UpdatePredefinedProperty $property): Predefined
    {
        return $this->repository->updatePredefinedProperty($property);
    }

    public function deletePredefinedProperty(string $id): void
    {
        $this->repository->deletePredefinedProperty($id);
    }
}
