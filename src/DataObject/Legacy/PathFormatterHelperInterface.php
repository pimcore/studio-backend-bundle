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

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

/**
 *  Copied from old admin-ui-classic-bundle
 *  https://github.com/pimcore/admin-ui-classic-bundle/blob/2013cc6e37f5b3dfffa9399921e0a12008b8bd8f/src/Controller/Admin/ElementController.php#L859
 *  Use with caution, this is a copy from the admin-ui-classic-bundle
 *
 * @internal
 */
interface PathFormatterHelperInterface
{
    public function getPathFormatterFieldDefinition(Concrete $source, array $context): Data|bool|null;
}
