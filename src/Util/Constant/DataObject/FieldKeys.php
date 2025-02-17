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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant\DataObject;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

enum FieldKeys: string
{
    use EnumToValueArrayTrait;

    case ID_KEY = 'id';
    case TYPE_KEY = 'type';
    case SUBTYPE_KEY = 'subtype';
    case FULL_PATH_KEY = 'fullPath';
    case IS_PUBLISHED_KEY = 'isPublished';
}
