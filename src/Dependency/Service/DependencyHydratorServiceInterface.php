<?php

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

use Pimcore\Bundle\StudioBackendBundle\Dependency\Request\DependencyParameters;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Result\ListingResult;
use Pimcore\Model\UserInterface;

interface DependencyHydratorServiceInterface
{
    public function getHydratedDependencies(
        DependencyParameters $parameters,
        UserInterface $user
    ): ListingResult;
}
