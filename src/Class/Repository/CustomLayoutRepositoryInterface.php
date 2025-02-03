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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Repository;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutNewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutUpdateParameters;
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
    public function getCustomLayouts(string $dataObjectClassId): array;

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
        CustomLayoutUpdateParameters $customLayoutParameters
    ): CustomLayout;

    public function exportCustomLayoutAsJson(CustomLayout $customLayout): string;

    /**
     * @throws NotWriteableException|JsonEncodingException|InvalidArgumentException
     */
    public function importCustomLayoutFromJson(CustomLayout $customLayout, string $json): CustomLayout;
}
