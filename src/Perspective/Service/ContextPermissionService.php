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
    private array $extraPermissions = [
        'applicationLog' => true,
        'emails' => true,
        'gdpr_data_extractor' => true,
        'glossary' => true,
        'hidden' => false,
        'maintenance' => true,
        'notesEvents' => true,
        'recycle_bin' => true,
        'redirects' => true,
        'systemTools_hidden' => false,
        'systemTools_requirements' => true,
        'translations' => true,
    ];

    private array $filePermissions = [
        'about' => true,
        'close_all' => true,
        'dashboards' => true,
        'help' => true,
        'hidden' => false,
        'open_asset' => true,
        'open_document' => true,
        'open_object' => true,
        'perspectives' => true,
        'schedule' => true,
        'searchReplace' => true,
        'see_mode' => true,
    ];

    private array $searchPermissions = [
        'assets' => true,
        'documents' => true,
        'hidden' => false,
        'objects' => true,
        'quickSearch' => true,
    ];

    private array $settingsPermissions = [
        'adminTranslations' => true,
        'appearance' => true,
        'cache_clearAll' => true,
        'cache_clearData' => true,
        'cache_clearOutput' => true,
        'cache_clearSymfony' => true,
        'cache_clearTemp' => true,
        'cache_hidden' => false,
        'customReports' => true,
        'documentTypes' => true,
        'hidden' => false,
        'object_bulkExport' => true,
        'object_bulkImport' => true,
        'object_classes' => true,
        'object_classificationstore' => true,
        'object_field_collections' => true,
        'object_hidden' => false,
        'object_objectbricks' => true,
        'object_quantityValue' => true,
        'predefinedMetadata' => true,
        'predefinedProperties' => true,
        'system' => true,
        'tagConfiguration' => true,
        'thumbnails' => true,
        'users_hidden' => false,
        'users_roles' => true,
        'users_users' => true,
        'website' => true,
    ];

    private array $contextPermissions = [];

    public function __construct()
    {
        $this->contextPermissions[ContextPermissionGroups::EXTRAS->value] = $this->extraPermissions;
        $this->contextPermissions[ContextPermissionGroups::FILE->value] = $this->filePermissions;
        $this->contextPermissions[ContextPermissionGroups::SEARCH->value] = $this->searchPermissions;
        $this->contextPermissions[ContextPermissionGroups::SETTINGS->value] = $this->settingsPermissions;
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
