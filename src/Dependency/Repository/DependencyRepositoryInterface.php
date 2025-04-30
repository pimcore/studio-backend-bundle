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
