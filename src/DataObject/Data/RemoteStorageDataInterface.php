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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data;

/**
 * Marker for data adapters whose field stores its value on other objects than the one being edited
 * (e.g. reverse relations). Edits of such fields are not applied where only the object's own values
 * are read, like the select options lookup, and are skipped while an auto-save stores a draft.
 * Third-party adapters can implement it as well.
 */
interface RemoteStorageDataInterface
{
}
