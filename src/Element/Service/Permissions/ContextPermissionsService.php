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

use Pimcore\Bundle\StudioBackendBundle\Element\Model\ContextPermissionData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ValidateElementTypeTrait;
use function sprintf;

/**
 * @internal
 */
final class ContextPermissionsService implements ContextPermissionsServiceInterface
{
    use ValidateElementTypeTrait;

    private array $assetContextPermissions = [
        'addUpload' => true,
        'uploadNewVersion' => true,
        'addUploadZip' => true,
        'download' => true,
        'downloadZip' => true,
        'addFolder' => true,
        'copy' => true,
        'cut' => true,
        'delete' => true,
        'lock' => true,
        'lockAndPropagate' => true,
        'paste' => true,
        'pasteCut' => true,
        'refresh' => true,
        'rename' => true,
        'searchAndMove' => true,
        'unlock' => true,
        'unlockAndPropagate' => true,
    ];

    private array $dataObjectContextPermissions = [
        'addObject' => true,
        'addFolder' => true,
        'addVariant' => true,
        'changeChildrenSortBy' => true,
        'copy' => true,
        'cut' => true,
        'delete' => true,
        'lock' => true,
        'lockAndPropagate' => true,
        'paste' => true,
        'publish' => true,
        'refresh' => true,
        'rename' => true,
        'searchAndMove' => true,
        'unlock' => true,
        'unlockAndPropagate' => true,
        'unpublish' => true,
    ];

    private array $documentContextPermissions = [
        'addEmail' => true,
        'addFolder' => true,
        'addHardlink' => true,
        'addLink' => true,
        'addPage' => true,
        'addSnippet' => true,
        'convert' => true,
        'copy' => true,
        'cut' => true,
        'delete' => true,
        'editSite' => true,
        'lock' => true,
        'lockAndPropagate' => true,
        'open' => true,
        'paste' => true,
        'pasteCut' => true,
        'publish' => true,
        'refresh' => true,
        'removeSite' => true,
        'rename' => true,
        'searchAndMove' => true,
        'unlock' => true,
        'unlockAndPropagate' => true,
        'unpublish' => true,
        'useAsSite' => true,
    ];

    private array $elementContextPermissions = [];

    public function __construct()
    {
        $this->elementContextPermissions[ElementTypes::TYPE_ASSET] = $this->assetContextPermissions;
        $this->elementContextPermissions[ElementTypes::TYPE_DATA_OBJECT] = $this->dataObjectContextPermissions;
        $this->elementContextPermissions[ElementTypes::TYPE_DOCUMENT] = $this->documentContextPermissions;
    }

    public function add(ContextPermissionData $contextPermissionData): void
    {
        $this->elementContextPermissions[$contextPermissionData->getElementType()][$contextPermissionData->getKey()] =
            $contextPermissionData->getDefaultValue();
    }

    public function getDefaultValue(string $key, string $elementType): bool
    {
        $this->validateStudioTypes($elementType);
        if (!isset($this->elementContextPermissions[$elementType][$key])) {
            throw new InvalidArgumentException(
                sprintf('Context permission with key "%s" does not exist', $key)
            );
        }

        return $this->elementContextPermissions[$elementType][$key];
    }

    /**
     * {@inheritdoc}
     */
    public function list(string $elementType): array
    {
        $this->validateStudioTypes($elementType);

        $elementPermissions = $this->elementContextPermissions[$elementType] ?? [];
        ksort($elementPermissions);

        return $elementPermissions;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key, string $elementType): void
    {
        $this->validateStudioTypes($elementType);

        unset($this->elementContextPermissions[$elementType][$key]);
    }
}
