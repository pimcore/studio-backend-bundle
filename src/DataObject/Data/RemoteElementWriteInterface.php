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
 * Marks a {@see SetterDataInterface} adapter whose getDataForSetter() persists other elements
 * (e.g. the owner objects of a reverse relation) instead of only preparing a value for the given element.
 * Such changes are live and not part of a draft, so they are skipped while an auto-save stores a draft.
 *
 * @internal
 */
interface RemoteElementWriteInterface
{
}
