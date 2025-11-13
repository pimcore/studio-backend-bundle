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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataColumn;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;

interface DataProviderInterface
{
    /**
     * Searches for personal data within this provider's domain.
     *
     * @param SearchTerms|null $terms The search values (can be null if none provided)
     *

     * @return array<array<string, mixed>>
     */
    public function findData(?SearchTerms $terms): array;

    /**
     * Returns the human-readable name for this provider.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the unique identifying key for this provider.
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * A higher number means a higher priority (appears first).
     *
     * @return int
     */
    public function getSortPriority(): int;

    /**
     * Returns the list of available columns for the result data.
     *
     * @return GdprDataColumn[]
     */
    public function getAvailableColumns(): array;

    /**
     * Returns the general UserPermission required to run this provider.
     *
     * @return UserPermissions
     */
    public function getRequiredPermission(): UserPermissions;

    /**
     * Fetches a single item's data for export.
     * The returned data will be serialized as JSON.
     * @param int $id
     * @return array|object
     *
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function getSingleItemForDownload(int $id): array|object;
}
