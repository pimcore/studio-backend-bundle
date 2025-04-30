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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service\Permissions;

use Pimcore\Bundle\StudioBackendBundle\Element\Hydrator\Permissions\AssetContextPermissionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Hydrator\Permissions\DataObjectContextPermissionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Hydrator\Permissions\DocumentContextPermissionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\AssetContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DataObjectContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DocumentContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveAssetContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveDataObjectContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveDocumentContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
final readonly class ContextPermissionsService implements ContextPermissionServiceInterface
{
    public function __construct(
        private AssetContextPermissionHydratorInterface $assetHydrator,
        private DataObjectContextPermissionHydratorInterface $dataObjectHydrator,
        private DocumentContextPermissionHydratorInterface $documentHydrator,
    ) {
    }

    /**
     * @throws InvalidElementTypeException
     */
    public function setElementContextPermissions(
        string $elementType,
        array $permissionData
    ): AssetContextPermissions|DataObjectContextPermissions|DocumentContextPermissions {
        return match ($elementType) {
            ElementTypes::TYPE_ASSET => $this->assetHydrator->hydrate($permissionData),
            ElementTypes::TYPE_DATA_OBJECT => $this->dataObjectHydrator->hydrate($permissionData),
            ElementTypes::TYPE_DOCUMENT => $this->documentHydrator->hydrate($permissionData),
            default => throw new InvalidElementTypeException($elementType),
        };
    }

    /**
     * @throws InvalidElementTypeException
     */
    public function saveElementContextPermissions(
        string $elementType,
        array $permissionData
    ): SaveAssetContextPermissions|SaveDataObjectContextPermissions|SaveDocumentContextPermissions {
        return match ($elementType) {
            ElementTypes::TYPE_ASSET => $this->assetHydrator->hydrateSavePermissions($permissionData),
            ElementTypes::TYPE_DATA_OBJECT => $this->dataObjectHydrator->hydrateSavePermissions($permissionData),
            ElementTypes::TYPE_DOCUMENT => $this->documentHydrator->hydrateSavePermissions($permissionData),
            default => throw new InvalidElementTypeException($elementType),
        };
    }
}
