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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\Helper\OptionsProviderResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUnits;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\OptionsUnavailableReason;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\SelectOptionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Helper\OptionsProviderResolver;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use ReflectionProperty;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;
use function array_key_exists;
use function array_map;
use function hash;
use function is_array;
use function json_decode;
use function method_exists;

/**
 * @internal
 */
final class KeyOptionsResolver implements KeyOptionsResolverInterface, ResetInterface
{
    private const string TYPE_PROPERTY = 'type';

    private const string PURPOSE = 'fielddefinition';

    /**
     * Deliberately a NON-cryptographic hash: the memo key only has to tell two blobs apart.
     */
    private const string MEMO_HASH = 'xxh128';

    /**
     * What every key already resolved publishes, keyed by the key's type and the hash of its
     * `definition` blob - the two the resolution reads and nothing else, so nothing invalidates it.
     *
     * @var array<string, array{
     *     options: SelectOption[]|null,
     *     reason: OptionsUnavailableReason|null,
     *     units: KeyUnits|null
     * }>
     */
    private array $resolved = [];

    public function __construct(
        private readonly ServiceResolverInterface $serviceResolver,
        private readonly OptionsProviderResolverInterface $optionsProviderResolver,
        private readonly SelectOptionHydratorInterface $selectOptionHydrator,
    ) {
    }

    /**
     * Drops the memo, so that a long-running worker does not carry every key it has ever seen.
     */
    public function reset(): void
    {
        $this->resolved = [];
    }

    public function getOptions(KeyConfig $keyConfig): ?array
    {
        return $this->resolve($keyConfig)['options'];
    }

    public function getOptionsUnavailableReason(KeyConfig $keyConfig): ?OptionsUnavailableReason
    {
        return $this->resolve($keyConfig)['reason'];
    }

    public function getUnits(KeyConfig $keyConfig): ?KeyUnits
    {
        return $this->resolve($keyConfig)['units'];
    }

    /**
     * @return array{options: SelectOption[]|null, reason: OptionsUnavailableReason|null, units: KeyUnits|null}
     */
    private function resolve(KeyConfig $keyConfig): array
    {
        // `$type` has no default: a key config that was never hydrated has no type to resolve by
        if (!(new ReflectionProperty($keyConfig, self::TYPE_PROPERTY))->isInitialized($keyConfig)) {
            return $this->publishNothing();
        }

        $memoKey = $keyConfig->getType() . "\0" . hash(self::MEMO_HASH, $keyConfig->getDefinition());
        if (array_key_exists($memoKey, $this->resolved)) {
            return $this->resolved[$memoKey];
        }

        $this->resolved[$memoKey] = $this->resolveDefinition($keyConfig);

        return $this->resolved[$memoKey];
    }

    /**
     * @return array{options: SelectOption[]|null, reason: OptionsUnavailableReason|null, units: KeyUnits|null}
     */
    private function resolveDefinition(KeyConfig $keyConfig): array
    {
        try {
            $decoded = json_decode($keyConfig->getDefinition(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->publishNothing();
        }

        if (!is_array($decoded)) {
            return $this->publishNothing();
        }

        // Rebuilt from the blob on every resolution, never the instance core memoises by key id
        $definition = $this->serviceResolver->getFieldDefinitionFromJson($decoded, $keyConfig->getType());

        if ($definition === null) {
            return $this->publishNothing();
        }

        $resolved = $this->publishNothing();
        $resolved['units'] = $this->resolveUnits($definition);

        if ($definition instanceof Select || $definition instanceof Multiselect) {
            [$resolved['options'], $resolved['reason']] = $this->resolveOptions($definition);
        }

        return $resolved;
    }

    /**
     * @return array{0: SelectOption[]|null, 1: OptionsUnavailableReason|null}
     */
    private function resolveOptions(Select|Multiselect $definition): array
    {
        try {
            if ($definition->useConfiguredOptions()) {
                return [$this->mapOptions($definition->getOptions() ?? []), null];
            }

            $provider = $this->optionsProviderResolver->resolveProvider(
                $definition->getOptionsProviderClass(),
                $definition instanceof Multiselect
                    ? OptionsProviderResolver::MODE_MULTISELECT
                    : OptionsProviderResolver::MODE_SELECT
            );

            if (!$provider instanceof SelectOptionsProviderInterface) {
                return [null, OptionsUnavailableReason::RESOLUTION_FAILED];
            }

            $context = ['object' => null, 'fieldname' => $definition->getName(), 'purpose' => self::PURPOSE];

            if (!$provider->hasStaticOptions($context, $definition)) {
                return [null, OptionsUnavailableReason::PROVIDER_NOT_STATIC];
            }

            return [$this->mapOptions($provider->getOptions($context, $definition)), null];
        } catch (Throwable) {
            return [null, OptionsUnavailableReason::RESOLUTION_FAILED];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $options
     *
     * @return SelectOption[]
     */
    private function mapOptions(array $options): array
    {
        return array_map(
            fn (array $option): SelectOption => $this->selectOptionHydrator->hydrate($option),
            $options
        );
    }

    /**
     * Read off the definition's own accessors rather than a list of types: every core definition
     * that holds a unit vocabulary exposes the same two.
     */
    private function resolveUnits(Data $definition): ?KeyUnits
    {
        if (!method_exists($definition, 'getDefaultUnit') || !method_exists($definition, 'getValidUnits')) {
            return null;
        }

        $validUnits = $definition->getValidUnits();

        return new KeyUnits($definition->getDefaultUnit(), $validUnits === [] ? null : $validUnits);
    }

    /**
     * @return array{options: null, reason: null, units: null}
     */
    private function publishNothing(): array
    {
        return ['options' => null, 'reason' => null, 'units' => null];
    }
}
