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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * @internal
 */
enum NavigationProperties: string
{
    use EnumToValueArrayTrait;

    case NAME = 'name';
    case TITLE = 'title';
    case TARGET = 'target';
    case EXCLUDE = 'exclude';
    case DOCUMENT_CLASS = 'class';
    case ANCHOR = 'anchor';
    case PARAMETERS = 'parameters';
    case RELATION = 'relation';
    case ACCESS_KEY = 'accesskey';
    case TABINDEX = 'tabindex';
}
