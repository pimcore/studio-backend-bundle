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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Model\ContextPermissionData;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\ContextPermissionGroups;
use function is_array;
use function sprintf;

/**
 * @internal
 */
final class ContextPermissionService implements ContextPermissionsServiceInterface
{
    private array $quickAccessPermissions = [
        'hidden' => false,
        'open_asset' => true,
        'open_document' => true,
        'open_object' => true,
        'recycle_bin' => true,
    ];

    private array $dataManagementPermissions = [
        'hidden' => false,
        'notesEvents' => true,
        'gdprDataExtractor' => true,
        'searchReplaceAssignments' => true,
        'predefinedProperties' => true,
        'tagConfiguration' => true,
        'dataModel_bulkExport' => true,
        'dataModel_bulkImport' => true,
        'dataModel_classes' => true,
        'dataModel_classificationStore' => true,
        'dataModel_fieldCollections' => true,
        'dataModel_hidden' => false,
        'dataModel_objectBricks' => true,
        'dataModel_quantityValue' => true,
        'dataModel_selectOptions' => true,
    ];

    private array $experienceEcommercePermissions = [
        'hidden' => false,
        'emails' => true,
        'documentTypes' => true,
        'websiteSettings' => true,
    ];

    private array $assetManagementPermissions = [
        'hidden' => false,
        'assetThumbnails' => true,
        'videoThumbnails' => true,
        'predefinedAssetMetadata' => true,
    ];

    private array $translationsPermissions = [
        'hidden' => false,
        'translations' => true,
    ];

    private array $systemPermissions = [
        'hidden' => false,
        'appearanceBranding' => true,
        'users_hidden' => false,
        'users_roles' => true,
        'users_users' => true,
        'perspectiveEditor' => true,
        'widgetEditor' => true,
        'maintenanceMode' => true,
        'cache_clearAll' => true,
        'cache_clearData' => true,
        'cache_clearOutput' => true,
        'cache_clearSymfony' => true,
        'cache_clearTemp' => true,
        'cache_hidden' => false,
        'systemSettings' => true,
        'about' => true,
        'ownershipManagement' => true,
    ];

    private array $searchPermissions = [
        'hidden' => false,
    ];

    private array $contextPermissions = [];

    public function __construct()
    {
        $this->contextPermissions[ContextPermissionGroups::QUICK_ACCESS->value] = $this->quickAccessPermissions;
        $this->contextPermissions[ContextPermissionGroups::DATA_MANAGEMENT->value] = $this->dataManagementPermissions;
        $this->contextPermissions[ContextPermissionGroups::EXPERIENCE_ECOMMERCE->value] = $this->experienceEcommercePermissions;
        $this->contextPermissions[ContextPermissionGroups::ASSET_MANAGEMENT->value] = $this->assetManagementPermissions;
        $this->contextPermissions[ContextPermissionGroups::TRANSLATIONS->value] = $this->translationsPermissions;
        $this->contextPermissions[ContextPermissionGroups::SYSTEM->value] = $this->systemPermissions;
        $this->contextPermissions[ContextPermissionGroups::SEARCH->value] = $this->searchPermissions;
    }

    public function add(ContextPermissionData $contextPermissionData): void
    {
        $this->contextPermissions[$contextPermissionData->getGroup()][$contextPermissionData->getKey()] =
            $contextPermissionData->getDefaultValue();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getDefaultValue(string $key, string $group): bool
    {
        if (!isset($this->contextPermissions[$group][$key])) {
            throw new InvalidArgumentException(
                sprintf('Context permission with key "%s" and group "%s" does not exist', $key, $group)
            );
        }

        return $this->contextPermissions[$group][$key];
    }

    public function list(): array
    {

        return $this->sortPermissionList($this->contextPermissions);
    }

    public function remove(string $key, string $group): void
    {
        unset($this->contextPermissions[$group][$key]);
    }

    private function sortPermissionList(array $array): array
    {
        ksort($array);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                ksort($value);
                $array[$key] = $value;
            }
        }

        return $array;
    }
}
