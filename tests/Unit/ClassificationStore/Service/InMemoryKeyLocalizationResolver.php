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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolverInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use SplObjectStorage;

/**
 * The key's `localized` flag held on the instance it was written to, the way the core accessor the
 * production resolver reaches holds it. The flag lives in a column of `classificationstore_keys`,
 * which this suite has no database for, and `KeyConfig` is final, so there is nothing to stub.
 *
 * This stands in for a core that CARRIES the flag; `new InMemoryKeyLocalizationResolver(false)` is
 * one that does not, where a read is null whatever was written and a write goes nowhere. The
 * production bridge that decides which of the two a core is has its own coverage in
 * {@see KeyLocalizationResolverTest}; this one exists so a caller of it can be tested either way.
 *
 * @internal
 */
final class InMemoryKeyLocalizationResolver implements KeyLocalizationResolverInterface
{
    /**
     * @var SplObjectStorage<KeyConfig, ?bool>
     */
    private SplObjectStorage $values;

    public function __construct(
        private readonly bool $supported = true
    ) {
        $this->values = new SplObjectStorage();
    }

    public function supportsLocalization(): bool
    {
        return $this->supported;
    }

    public function getLocalized(KeyConfig $keyConfig): ?bool
    {
        if (!$this->supported) {
            return null;
        }

        return $this->values[$keyConfig] ?? null;
    }

    public function setLocalized(KeyConfig $keyConfig, ?bool $localized): void
    {
        if (!$this->supported) {
            return;
        }

        $this->values[$keyConfig] = $localized;
    }
}
