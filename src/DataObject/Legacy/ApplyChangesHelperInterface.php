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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Legacy;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Concrete;

/**
 * Copied from old admin-ui-classic-bundle
 * https://github.com/pimcore/admin-ui-classic-bundle/blob/e71ee902ab1274a64d6b80d56af28f1855944dfd/src/Controller/Admin/DataObject/DataObjectController.php#L597
 * Use with caution, this is a copy from the admin-ui-classic-bundle
 *
 * @internal
 */
interface ApplyChangesHelperInterface
{
    /**
     * @throws NotFoundException
     * @throws DatabaseException
     */
    public function applyChanges(Concrete $object, array $changes): void;
}
