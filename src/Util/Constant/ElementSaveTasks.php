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

    /**
     * Draft saves (version/autosave) and unpublishing persist without mandatory checks
     * (see ElementSaveService). Field-level validation that runs earlier, while the data
     * adapters bind the payload, has to honour the same tasks.
     */
    public static function omitsMandatoryCheck(?string $task): bool
    {
        return in_array(
            $task,
            [self::AUTOSAVE->value, self::VERSION->value, self::UNPUBLISH->value],
            true
        );
    }

    /**
     * The omitMandatoryCheck flag an element needs while the data adapters bind the payload,
     * or null where core's fallback (omit while the element is unpublished) is already right.
     * ElementSaveService and the publish adapters change the published state and the flag only
     * while saving — during binding the element still carries its old published state, so a
     * request that publishes a still unpublished element would wrongly skip the mandatory
     * validation without an explicit override. $published is the raw `published` payload value.
     */
    public static function resolveOmitMandatoryCheckForBinding(?string $task, mixed $published): ?bool
    {
        if (self::omitsMandatoryCheck($task) || $published === false) {
            return true;
        }

        if ($task === self::PUBLISH->value || $published === true) {
            return false;
        }

        return null;
    }
}
