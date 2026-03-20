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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Util\Constant;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * @internal
 */
enum FilterableFields: string
{
    use EnumToValueArrayTrait;

    case NAME = 'name';
    case DESCRIPTION = 'description';
    case TYPE = 'type';
    case TARGET_SUBTYPE = 'targetSubtype';
    case DATA = 'data';
    case CONFIG = 'config';
    case LANGUAGE = 'language';
    case GROUP = 'group';
}
