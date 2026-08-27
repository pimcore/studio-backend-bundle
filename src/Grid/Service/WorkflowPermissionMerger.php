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
use Symfony\Component\Workflow\Exception\LogicException;
use function array_merge;
use function count;

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
        $workflowPermissions = $this->getWorkflowUserPermissions($element);

        if (count($workflowPermissions) === 0) {
            return $permissions;
        }

        $view = $permissions->isView() && !$this->isDenied($workflowPermissions, 'view');
        $publish = $permissions->isPublish() && !$this->isDenied($workflowPermissions, 'publish');
        $delete = $permissions->isDelete() && !$this->isDenied($workflowPermissions, 'delete');
        $rename = $permissions->isRename() && !$this->isDenied($workflowPermissions, 'rename');
        $settings = $permissions->isSettings() && !$this->isDenied($workflowPermissions, 'settings');
        $versions = $permissions->isVersions() && !$this->isDenied($workflowPermissions, 'versions');
        $properties = $permissions->isProperties() && !$this->isDenied($workflowPermissions, 'properties');

        return match (true) {
            $permissions instanceof DataObjectPermissions => new DataObjectPermissions(
                save: $permissions->isSave() && !$this->isDenied($workflowPermissions, 'save'),
                unpublish: $permissions->isUnpublish() && !$this->isDenied($workflowPermissions, 'unpublish'),
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
                save: $permissions->isSave() && !$this->isDenied($workflowPermissions, 'save'),
                unpublish: $permissions->isUnpublish() && !$this->isDenied($workflowPermissions, 'unpublish'),
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

    /**
     * Evaluates the element's workflow state once and returns the merged place permission map.
     *
     * Mirrors the merge semantics of the private \Pimcore\Workflow\Manager::getWorkflowUserPermissions()
     * (which backs Manager::isDeniedInWorkflow()) so a grid row triggers a single workflow evaluation
     * instead of one per permission type.
     *
     * @return array<string, mixed>
     */
    private function getWorkflowUserPermissions(ElementInterface $element): array
    {
        $userPermissions = [];
        foreach ($this->workflowManager->getAllWorkflows() as $workflowName) {
            $workflow = $this->workflowManager->getWorkflowIfExists($element, $workflowName);

            if ($workflow === null) {
                continue;
            }

            try {
                $marking = $workflow->getMarking($element);
            } catch (LogicException) {
                continue;
            }

            if (count($marking->getPlaces()) === 0) {
                continue;
            }

            foreach ($this->workflowManager->getOrderedPlaceConfigs($workflow, $marking) as $placeConfig) {
                if (count($placeConfig->getPermissions($workflow, $element)) > 0) {
                    $userPermissions = array_merge(
                        $userPermissions,
                        $placeConfig->getUserPermissions($workflow, $element)
                    );
                }
            }
        }

        return $userPermissions;
    }

    /**
     * @param array<string, mixed> $workflowPermissions
     */
    private function isDenied(array $workflowPermissions, string $permissionType): bool
    {
        return ($workflowPermissions[$permissionType] ?? null) === false;
    }
}
