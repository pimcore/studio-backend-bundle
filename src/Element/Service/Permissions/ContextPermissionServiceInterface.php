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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service\Permissions;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\AssetContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DataObjectContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DocumentContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveAssetContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveDataObjectContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveDocumentContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;

/**
 * @internal
 */
interface ContextPermissionServiceInterface
{
    /**
     * @throws InvalidElementTypeException
     */
    public function setElementContextPermissions(
        string $elementType,
        array $permissionData
    ): AssetContextPermissions|DataObjectContextPermissions|DocumentContextPermissions;

    /**
     * @throws InvalidElementTypeException
     */
    public function saveElementContextPermissions(
        string $elementType,
        array $permissionData
    ): SaveAssetContextPermissions|SaveDataObjectContextPermissions|SaveDocumentContextPermissions;
}
