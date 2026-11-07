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

    public function supportsLocalization(): bool
    {
        return $this->declaresAccessor(self::GETTER) && $this->declaresAccessor(self::SETTER);
    }

    public function getLocalized(KeyConfig $keyConfig): ?bool
    {
        return $this->readFlag($keyConfig);
    }

    public function setLocalized(KeyConfig $keyConfig, ?bool $localized): void
    {
        $this->writeFlag($keyConfig, $localized);
    }

    /**
     * {@see self::getLocalized()} against any key configuration model.
     *
     * Public, and typed on the model rather than on `KeyConfig`, so that both halves of the bridge
     * can be covered: `KeyConfig` is final and the core this bundle resolves need not declare the
     * accessors at all, so a real one can only ever exercise the fallback. Production call sites
     * bind to {@see KeyLocalizationResolverInterface} and never reach this.
     */
    public function readFlag(object $keyConfig): ?bool
    {
        $value = $this->accessor($keyConfig, self::GETTER);

        return is_bool($value) ? $value : null;
    }

    /**
     * {@see self::setLocalized()} against any key configuration model, public for the reason
     * {@see self::readFlag()} is.
     */
    public function writeFlag(object $keyConfig, ?bool $localized): void
    {
        $this->accessor($keyConfig, self::SETTER, $localized);
    }

    /**
     * Whether the core's key configuration declares the accessor at all. Asked by name, for the
     * reason {@see self::accessor()} calls by name: the method is not this package's and the core
     * whose it is need not have it.
     */
    private function declaresAccessor(string $accessor): bool
    {
        return method_exists(KeyConfig::class, $accessor);
    }

    /**
     * The accessor by name: `composer.json` allows a core whose `classificationstore_keys` has no
     * `localized` column yet, and against one of those every key states no flag and nothing is
     * written, rather than the model being reached for a method it does not carry.
     */
    private function accessor(object $keyConfig, string $accessor, mixed ...$arguments): mixed
    {
        if (!method_exists($keyConfig, $accessor)) {
            return null;
        }

        return $keyConfig->$accessor(...$arguments);
    }
}
