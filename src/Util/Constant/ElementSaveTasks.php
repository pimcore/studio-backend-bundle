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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;
use function in_array;

/**
 * @internal
 */
enum ElementSaveTasks: string
{
    use EnumToValueArrayTrait;

    case AUTOSAVE = 'autoSave';
    case PUBLISH = 'publish';
    case SAVE = 'save';
    case UNPUBLISH = 'unpublish';
    case VERSION = 'version';

    /**
     * Only an explicit save may change other, live elements (e.g. the owners of a reverse relation).
     * Auto-save and unknown tasks must not, as they store nothing explicit or nothing at all.
     */
    public static function appliesRemoteChanges(?string $task): bool
    {
        return $task === null || in_array(
            $task,
            [self::PUBLISH->value, self::SAVE->value, self::UNPUBLISH->value, self::VERSION->value],
            true
        );
    }
}
