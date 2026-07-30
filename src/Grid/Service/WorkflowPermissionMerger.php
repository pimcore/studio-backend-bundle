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

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetPermissions;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectPermissions;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Manager;

/**
 * @internal
 */
final readonly class WorkflowPermissionMerger implements WorkflowPermissionMergerInterface
{
    public function __construct(
        private Manager $workflowManager
    ) {
    }

    public function mergeWorkflowPermissions(Permissions $permissions, ElementInterface $element): Permissions
    {
        $view = $permissions->isView() && !$this->isDenied($element, 'view');
        $publish = $permissions->isPublish() && !$this->isDenied($element, 'publish');
        $delete = $permissions->isDelete() && !$this->isDenied($element, 'delete');
        $rename = $permissions->isRename() && !$this->isDenied($element, 'rename');
        $settings = $permissions->isSettings() && !$this->isDenied($element, 'settings');
        $versions = $permissions->isVersions() && !$this->isDenied($element, 'versions');
        $properties = $permissions->isProperties() && !$this->isDenied($element, 'properties');

        return match (true) {
            $permissions instanceof DataObjectPermissions => new DataObjectPermissions(
                save: $permissions->isSave() && !$this->isDenied($element, 'save'),
                unpublish: $permissions->isUnpublish() && !$this->isDenied($element, 'unpublish'),
                localizedEdit: $permissions->getLocalizedEdit(),
                localizedView: $permissions->getLocalizedView(),
                list: $permissions->isList(),
                view: $view,
                publish: $publish,
                delete: $delete,
                rename: $rename,
                create: $permissions->isCreate(),
                settings: $settings,
                versions: $versions,
                properties: $properties,
            ),
            $permissions instanceof DocumentPermissions => new DocumentPermissions(
                save: $permissions->isSave() && !$this->isDenied($element, 'save'),
                unpublish: $permissions->isUnpublish() && !$this->isDenied($element, 'unpublish'),
                list: $permissions->isList(),
                view: $view,
                publish: $publish,
                delete: $delete,
                rename: $rename,
                create: $permissions->isCreate(),
                settings: $settings,
                versions: $versions,
                properties: $properties,
            ),
            $permissions instanceof AssetPermissions => new AssetPermissions(
                list: $permissions->isList(),
                view: $view,
                publish: $publish,
                delete: $delete,
                rename: $rename,
                create: $permissions->isCreate(),
                settings: $settings,
                versions: $versions,
                properties: $properties,
            ),
            default => new Permissions(
                list: $permissions->isList(),
                view: $view,
                publish: $publish,
                delete: $delete,
                rename: $rename,
                create: $permissions->isCreate(),
                settings: $settings,
                versions: $versions,
                properties: $properties,
            ),
        };
    }

    private function isDenied(ElementInterface $element, string $permissionType): bool
    {
        return $this->workflowManager->isDeniedInWorkflow($element, $permissionType);
    }
}
