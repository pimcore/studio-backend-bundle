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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutNewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayout;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\JsonEncodingException;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout as CoreLayout;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\Response;

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
     * @param string[] $dataObjectClassIds
     *
     * @return CustomLayoutCompact[]
     */
    public function getCustomLayoutCollection(array $dataObjectClassIds): array;

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
        UpdateParameters $customLayoutParameters
    ): CustomLayout;

    /**
     * @throws NotFoundException
     */
    public function exportCustomLayoutAsJson(string $customLayoutId): Response;

    /**
     * @throws NotFoundException|NotWriteableException|JsonEncodingException|InvalidArgumentException
     */
    public function importCustomLayoutActionFromJson(string $customLayoutId, string $json): CustomLayout;

    public function getMainLayout(): CoreLayout;

    public function getMainAdminLayout(): CoreLayout;

    /**
     * @throws NotFoundException
     */
    public function getBrickCustomLayout(string $key, string $customLayoutId): CustomLayout;

    /**
     * @throws NotFoundException|NotWriteableException|InvalidArgumentException|EnvironmentException
     */
    public function updateBrickCustomLayout(
        string $key,
        string $customLayoutId,
        UpdateParameters $parameters,
    ): CustomLayout;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function deleteBrickCustomLayout(string $key, string $customLayoutId): void;

    /**
     * @throws NotFoundException
     */
    public function exportBrickCustomLayoutAsJson(string $key, string $customLayoutId): Response;

    /**
     * @throws NotFoundException|NotWriteableException|JsonEncodingException|InvalidArgumentException
     */
    public function importBrickCustomLayoutFromJson(
        string $key,
        string $customLayoutId,
        string $json,
    ): CustomLayout;
}
