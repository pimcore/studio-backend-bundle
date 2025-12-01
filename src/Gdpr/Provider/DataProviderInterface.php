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
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataColumn;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\HttpFoundation\Response;

interface DataProviderInterface
{
    public function findData(SearchTerms $terms, FilterParameter $options): Collection;

    public function getDeleteSwaggerOperationId(): string;

    public function getName(): string;

    public function getKey(): string;

    public function getSortPriority(): int;

    /**
     * @return GdprDataColumn[]
     */
    public function getAvailableColumns(): array;

    /**
     * @return string[] (e.g., ['users', 'objects'])
     */
    public function getRequiredPermissions(): array;

    /**
     * @throws NotFoundException
     */
    public function getSingleItemForDownload(int $id): array|Response;
}
