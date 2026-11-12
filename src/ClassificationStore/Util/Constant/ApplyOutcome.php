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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * What apply decided about one entry. `ADOPTED` is the third category beside created and updated:
 * a target row that carried no identifier and was matched by name now carries the file's one.
 * `SKIPPED` is an entry the apply left alone because applying it would have meant guessing: its name is
 * another row's in the target, under another identifier.
 *
 * @internal
 */
enum ApplyOutcome: string
{
    use EnumToValueArrayTrait;

    case CREATED = 'created';
    case UPDATED = 'updated';
    case ADOPTED = 'adopted';
    case MATCHED_UNCHANGED = 'matchedUnchanged';
    case TARGET_ONLY = 'targetOnly';
    case SKIPPED = 'skipped';
    case CONFLICT = 'conflicts';
}
