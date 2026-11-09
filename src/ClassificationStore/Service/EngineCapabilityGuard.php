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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class EngineCapabilityGuard implements EngineCapabilityGuardInterface
{
    public function assertSupported(array $values, ?array $capabilities): void
    {
        // a capability that could not be confirmed is one the write does not get
        $reported = $capabilities ?? [];

        $this->assertFeature(
            $this->needsVariants($values),
            StoreEngineResolverInterface::FEATURE_VARIANTS,
            $reported,
            'a variant other than the default one'
        );

        $this->assertFeature(
            $this->needsMultiValue($values),
            StoreEngineResolverInterface::FEATURE_MULTI_VALUE,
            $reported,
            'more than one value per variant'
        );
    }

    /**
     * @param string[] $capabilities
     *
     * @throws InvalidArgumentException
     */
    private function assertFeature(bool $needed, string $feature, array $capabilities, string $what): void
    {
        if (!$needed || in_array($feature, $capabilities, true)) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf('The engine of the key\'s store does not support %s.', $what),
            errorKey: HttpResponseErrorKeys::ENGINE_CAPABILITY_MISSING->value
        );
    }

    /**
     * @param QualifiedValue[] $values
     */
    private function needsVariants(array $values): bool
    {
        foreach ($values as $value) {
            if ($value->getVariant() !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * A position other than the first, which is what a repeatable key holds a second value at.
     * Two values of two different variants both sit at position 0 and need `Variants` instead, so
     * the length of the list is not what decides here.
     *
     * @param QualifiedValue[] $values
     */
    private function needsMultiValue(array $values): bool
    {
        foreach ($values as $value) {
            if ($value->getPosition() !== QualifiedValueSerializerInterface::DEFAULT_POSITION) {
                return true;
            }
        }

        return false;
    }
}
