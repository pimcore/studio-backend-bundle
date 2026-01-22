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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\HttpFoundation\Response;

interface DataProviderInterface
{
    /**
     * @return Collection<GdprDataRow>
     */
    public function findData(FilterParameter $filter): Collection;

    public function getDeleteSwaggerOperationId(): string;

    public function getName(): string;

    public function getKey(): string;

    public function getSortPriority(): int;

    /**
     * @return string[] (e.g., ['users', 'objects'])
     */
    public function getRequiredPermissions(): array;

    /**
     * @throws NotFoundException
     */
    public function getSingleItemForDownload(int $id): array|Response;
}
