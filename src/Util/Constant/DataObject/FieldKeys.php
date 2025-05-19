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
