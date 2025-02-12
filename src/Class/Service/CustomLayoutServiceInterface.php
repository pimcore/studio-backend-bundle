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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutNewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutUpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayout;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\JsonEncodingException;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout as CoreLayout;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @internal
 */
interface CustomLayoutServiceInterface
{
    /**
     * @throws ForbiddenException|NotFoundException
     *
     * @return CustomLayoutCompact[]
     */
    public function getCustomLayoutEditorCollection(
        int $dataObjectId,
        UserInterface $user
    ): array;

    /**
     * @return CustomLayoutCompact[]
     */
    public function getCustomLayoutCollection(string $dataObjectClass): array;

    /**
     * @throws NotFoundException
     */
    public function getCustomLayout(string $customLayoutId): CustomLayout;

    /**
     * @return CoreLayout[]
     */
    public function getUserCustomLayouts(DataObject $dataObject, UserInterface $user, array $allowedLayouts): array;

    /**
     * @throws NotWriteableException|NotFoundException
     */
    public function deleteCustomLayout(string $customLayoutId): void;

    /**
     * @throws NotWriteableException
     */
    public function createCustomLayout(
        string $customLayoutId,
        CustomLayoutNewParameters $customLayoutParameters
    ): CustomLayout;

    /**
     * @throws NotWriteableException|NotFoundException|InvalidArgumentException
     */
    public function updateCustomLayout(
        string $customLayoutId,
        CustomLayoutUpdateParameters $customLayoutParameters
    ): CustomLayout;

    /**
     * @throws NotFoundException
     */
    public function exportCustomLayoutAsJson(string $customLayoutId): JsonResponse;

    /**
     * @throws NotFoundException|NotWriteableException|JsonEncodingException|InvalidArgumentException
     */
    public function importCustomLayoutActionFromJson(string $customLayoutId, string $json): CustomLayout;

    public function getMainLayout(): CoreLayout;

    public function getMainAdminLayout(): CoreLayout;
}
