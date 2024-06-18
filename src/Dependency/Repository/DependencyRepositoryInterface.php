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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Element\SearchResult\ElementSearchResult;
use Pimcore\Bundle\StudioBackendBundle\Dependency\MappedParameter\DependencyParameters;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface DependencyRepositoryInterface
{
    public function listRequiresDependencies(
        ElementParameters $elementParameters,
        DependencyParameters $parameters,
        UserInterface $user
    ): ElementSearchResult;

    public function listRequiredByDependencies(
        ElementParameters $elementParameters,
        DependencyParameters $parameters,
        UserInterface $user
    ): ElementSearchResult;
}
