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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ColumnFieldDefinition;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface ColumnConfigurationServiceInterface
{
    /**
     * @return ColumnConfiguration[]
     */
    public function getAvailableAssetColumnConfiguration(): array;

    /**
     * @return ColumnConfiguration[]
     */
    public function getAvailableDataObjectColumnConfiguration(
        ?string $classId,
        ?int $folderId,
        UserInterface $user
    ): array;

    /**
     * @return ColumnConfiguration[]
     */
    public function getSystemDataObjectColumnConfiguration(): array;

    /**
     * Builds a column configuration for a data object adapter column.
     * key type and is optional and can be used to build a column configuration for a specific key type.
     *
     * @throws InvalidArgumentException
     */
    public function buildDataObjectAdapterColumnConfiguration(
        ColumnFieldDefinition $definition,
        ?string $type = null,
        ?string $key = null,
        ?array $additionalConfig = null
    ): ColumnConfiguration;
}
