<?php

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

enum ContainerTypes: string
{
    use EnumToValueArrayTrait;

    case BLOCK = 'block';
    case CLASSIFICATION_STORE = 'classificationstore';
    case FIELD_COLLECTION = 'fieldcollection';
    case OBJECT_BRICK = 'objectbrick';
}
