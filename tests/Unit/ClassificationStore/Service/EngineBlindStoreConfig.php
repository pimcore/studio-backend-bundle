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
 * A store configuration model of a core whose `classificationstore_stores` has NO `engine` column:
 * no accessor for it and no `__call` to absorb one either, so a bridge that skipped its guard
 * raises an `Error` here rather than quietly answering null.
 *
 * @internal
 */
final class EngineBlindStoreConfig
{
    public function getId(): ?int
    {
        return 1;
    }
}
