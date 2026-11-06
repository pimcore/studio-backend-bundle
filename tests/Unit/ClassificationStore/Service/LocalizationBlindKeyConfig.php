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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

/**
 * A key configuration model of a core whose `classificationstore_keys` has NO `localized` column:
 * no accessor for the flag and no `__call` to absorb one either, so a bridge that skipped its guard
 * raises an `Error` here rather than quietly answering null.
 *
 * @internal
 */
final class LocalizationBlindKeyConfig
{
    public function getName(): string
    {
        return 'weight';
    }
}
