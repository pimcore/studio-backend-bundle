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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\Filter;

use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface TreePqlServiceInterface
{
    /**
     * Only elements the workspaces of the user allow to view contribute folder keys, so the tree does not reveal
     * where elements the user may not view match the query. Without a user, no folder keys are returned.
     *
     * @return string[] Folder key names
     */
    public function getRelevantChildFolderKeys(
        int $parentId,
        string $pqlQuery,
        string $elementType,
        ?UserInterface $user,
    ): array;
}
