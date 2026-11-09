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

use JsonException;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function method_exists;

/**
 * @internal
 */
final readonly class KeyQualifierResolver implements KeyQualifierResolverInterface
{
    private const string VARIANTS_GETTER = 'getVariants';

    private const string MULTI_VALUE_GETTER = 'getMultiValue';

    public function declaresQualifiers(KeyConfig $keyConfig): bool
    {
        $variants = $this->getVariants($keyConfig);

        return ($variants !== null && $variants !== []) || $this->isMultiValue($keyConfig) === true;
    }

    public function getVariants(KeyConfig $keyConfig): ?array
    {
        $declared = $this->accessor($keyConfig, self::VARIANTS_GETTER)
            ?? $this->member($keyConfig, self::VARIANTS_MEMBER);

        if (!is_array($declared)) {
            return null;
        }

        $names = [];
        foreach ($declared as $name) {
            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function isMultiValue(KeyConfig $keyConfig): ?bool
    {
        $declared = $this->accessor($keyConfig, self::MULTI_VALUE_GETTER)
            ?? $this->member($keyConfig, self::MULTI_VALUE_MEMBER);

        return is_bool($declared) ? $declared : null;
    }

    /**
     * The member as the key's `definition` blob carries it, which is where the declaration lives.
     * A blob that is absent or not readable as JSON declares nothing rather than failing the read.
     */
    private function member(KeyConfig $keyConfig, string $member): mixed
    {
        $definition = $keyConfig->getDefinition();

        if ($definition === '') {
            return null;
        }

        try {
            $decoded = json_decode($definition, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? ($decoded[$member] ?? null) : null;
    }

    /**
     * The accessor by name: a later core may carry the declaration as a column of its own, and
     * against one that does not the member of the `definition` blob is what answers. Asked by name
     * for the reason {@see KeyLocalizationResolver::accessor()} asks by name - the method is not
     * this package's and the core whose it is need not have it.
     */
    private function accessor(KeyConfig $keyConfig, string $accessor): mixed
    {
        if (!method_exists($keyConfig, $accessor)) {
            return null;
        }

        return $keyConfig->$accessor();
    }
}
