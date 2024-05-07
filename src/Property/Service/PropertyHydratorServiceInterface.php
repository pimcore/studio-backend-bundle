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

use Pimcore\Bundle\StudioBackendBundle\Property\Request\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Model\Property\Predefined;

interface PropertyHydratorServiceInterface
{
    /**
     * @return PredefinedProperty[]
     */
    public function getHydratedProperties(PropertiesParameters $parameters): array;

    public function getHydratedPropertyForElement(string $elementType, int $id): array;

    public function getHydratedPredefinedProperty(Predefined $predefined): PredefinedProperty;
}
