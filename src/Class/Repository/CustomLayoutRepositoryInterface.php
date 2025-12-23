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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Repository;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutNewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\JsonEncodingException;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

/**
 * @internal
 */
interface CustomLayoutRepositoryInterface
{
    /**
     * @return CustomLayout[]
     */
    public function getCustomLayoutsByClass(string $dataObjectClassId): array;

    public function getAllCustomLayouts(): array;

    /**
     * @throws NotFoundException
     */
    public function getCustomLayout(string $customLayoutId): CustomLayout;

    /**
     * @throws NotWriteableException
     */
    public function deleteCustomLayout(CustomLayout $customLayout): void;

    /**
     * @throws NotWriteableException
     */
    public function createCustomLayout(
        string $customLayoutId,
        CustomLayoutNewParameters $customLayoutParameters
    ): CustomLayout;

    /**
     * @throws NotWriteableException|InvalidArgumentException
     */
    public function updateCustomLayout(
        CustomLayout $customLayout,
        UpdateParameters $customLayoutParameters
    ): CustomLayout;

    public function exportCustomLayoutAsJson(CustomLayout $customLayout): string;

    /**
     * @throws NotWriteableException|JsonEncodingException|InvalidArgumentException
     */
    public function importCustomLayoutFromJson(CustomLayout $customLayout, string $json): CustomLayout;
}
