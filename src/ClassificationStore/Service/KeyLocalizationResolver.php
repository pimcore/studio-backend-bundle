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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function is_bool;
use function method_exists;

/**
 * @internal
 */
final readonly class KeyLocalizationResolver implements KeyLocalizationResolverInterface
{
    private const string GETTER = 'getLocalized';

    private const string SETTER = 'setLocalized';

    public function getLocalized(KeyConfig $keyConfig): ?bool
    {
        $value = $this->accessor($keyConfig, self::GETTER);

        return is_bool($value) ? $value : null;
    }

    public function setLocalized(KeyConfig $keyConfig, ?bool $localized): void
    {
        $this->accessor($keyConfig, self::SETTER, $localized);
    }

    /**
     * The accessor by name: `composer.json` allows a core whose `classificationstore_keys` has no
     * `localized` column yet, and against one of those every key states no flag and nothing is
     * written, rather than the model being reached for a method it does not carry.
     */
    private function accessor(KeyConfig $keyConfig, string $accessor, mixed ...$arguments): mixed
    {
        if (!method_exists($keyConfig, $accessor)) {
            return null;
        }

        return $keyConfig->$accessor(...$arguments);
    }
}
