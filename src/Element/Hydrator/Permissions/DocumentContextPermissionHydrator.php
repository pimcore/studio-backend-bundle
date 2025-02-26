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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Hydrator\Permissions;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DocumentContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveDocumentContextPermissions;

/**
 * @internal
 */
final readonly class DocumentContextPermissionHydrator implements DocumentContextPermissionHydratorInterface
{
    public function hydrate(array $data): DocumentContextPermissions
    {
        return new DocumentContextPermissions(...$this->extractPermissions($data));
    }

    public function hydrateSavePermissions(array $data): SaveDocumentContextPermissions
    {
        return new SaveDocumentContextPermissions(...$this->extractPermissions($data));
    }

    private function extractPermissions(array $data): array
    {
        return [
            $data['add'] ?? true,
            $data['addEmail'] ?? true,
            $data['addFolder'] ?? true,
            $data['addHardlink'] ?? true,
            $data['addHeadlessDocument'] ?? true,
            $data['addLink'] ?? true,
            $data['addNewsletter'] ?? true,
            $data['addPrintPage'] ?? true,
            $data['addSnippet'] ?? true,
            $data['convert'] ?? true,
            $data['copy'] ?? true,
            $data['cut'] ?? true,
            $data['delete'] ?? true,
            $data['editSite'] ?? true,
            $data['lock'] ?? true,
            $data['lockAndPropagate'] ?? true,
            $data['open'] ?? true,
            $data['paste'] ?? true,
            $data['pasteCut'] ?? true,
            $data['publish'] ?? true,
            $data['refresh'] ?? true,
            $data['removeSite'] ?? true,
            $data['rename'] ?? true,
            $data['searchAndMove'] ?? true,
            $data['unlock'] ?? true,
            $data['unlockAndPropagate'] ?? true,
            $data['unpublish'] ?? true,
            $data['useAsSite'] ?? true,
        ];
    }
}
