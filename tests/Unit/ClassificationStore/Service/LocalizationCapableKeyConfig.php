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
 * A key configuration model of a core that CARRIES the `localized` column: the two accessors such a
 * core declares, and nothing else the compatibility bridge is allowed to depend on.
 *
 * `Pimcore\Model\DataObject\Classificationstore\KeyConfig` is final and the core this bundle resolves
 * need not declare the accessors at all, so the capable half of the bridge cannot be read against the
 * real model. The column is held as `mixed` rather than as `?bool` so that a core handing the raw
 * column over - a tinyint, say - can be read against as well.
 *
 * @internal
 */
final class LocalizationCapableKeyConfig
{
    public mixed $localized = null;

    public function getLocalized(): mixed
    {
        return $this->localized;
    }

    public function setLocalized(?bool $localized): void
    {
        $this->localized = $localized;
    }
}
