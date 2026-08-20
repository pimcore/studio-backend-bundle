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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ObjectDependencies;

/**
 * @internal
 */
interface ObjectDependenciesServiceInterface
{
    /**
     * @return Collection<\Pimcore\Bundle\StudioBackendBundle\User\Schema\Dependency>
     */
    public function getPaginatedDependenciesForUser(int $userId, CollectionParameters $parameters): Collection;

    /**
     * A bounded preview for embedding in the main user payload, keeping the
     * hasHidden/dependencies shape this field has always had.
     */
    public function getPreviewForUser(int $userId, int $previewSize): ObjectDependencies;
}
