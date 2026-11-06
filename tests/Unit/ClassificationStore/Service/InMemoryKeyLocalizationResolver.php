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
 * @internal
 */
final class InMemoryKeyLocalizationResolver implements KeyLocalizationResolverInterface
{
    /**
     * @var SplObjectStorage<KeyConfig, ?bool>
     */
    private SplObjectStorage $values;

    public function __construct()
    {
        $this->values = new SplObjectStorage();
    }

    public function getLocalized(KeyConfig $keyConfig): ?bool
    {
        return $this->values[$keyConfig] ?? null;
    }

    public function setLocalized(KeyConfig $keyConfig, ?bool $localized): void
    {
        $this->values[$keyConfig] = $localized;
    }
}
