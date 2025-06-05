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

enum DocumentFieldKeys: string
{
    case SETTINGS_DATA = 'settingsData';
    case EDITABLE_DATA = 'editableData';
    case MISSING_REQUIRED_EDITABLE = 'missingRequiredEditable';
}
