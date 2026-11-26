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
 * A store configuration model of a core that DOES carry the `engine` column, standing in for the
 * final `StoreConfig` the production bridge reaches by name.
 *
 * @internal
 */
final class EngineAwareStoreConfig
{
    public mixed $engine = null;

    public function getId(): ?int
    {
        return 1;
    }

    public function getEngine(): mixed
    {
        return $this->engine;
    }
}
