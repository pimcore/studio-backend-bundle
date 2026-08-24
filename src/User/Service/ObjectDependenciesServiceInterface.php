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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ObjectDependencies;

/**
 * @internal
 */
interface ObjectDependenciesServiceInterface
{
    // Matches the largest selectable page size in the Studio UI's pagination control.
    // Enforced here (not on the shared CollectionParameters) since, unlike most paginated
    // endpoints, every additional item costs a real DataObject hydration + permission check.
    public const int MAX_PAGE_SIZE = 100;

    /**
     * @return Collection<\Pimcore\Bundle\StudioBackendBundle\User\Schema\Dependency>
     *
     * @throws NotFoundException|ForbiddenException|InvalidFilterException
     */
    public function getPaginatedDependenciesForUser(int $userId, CollectionParameters $parameters): Collection;

    /**
     * A bounded preview for embedding in the main user payload, keeping the
     * hasHidden/dependencies shape this field has always had.
     */
    public function getPreviewForUser(int $userId, int $previewSize): ObjectDependencies;
}
