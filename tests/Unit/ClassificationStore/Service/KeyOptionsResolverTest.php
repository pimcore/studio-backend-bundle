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

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\Helper\OptionsProviderResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyOptionsResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\OptionsUnavailableReason;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\SelectOptionHydrator;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValueRange;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use ReflectionProperty;
use stdClass;
use Throwable;
use TypeError;
use function array_map;
use function json_encode;

/**
 * The one place a key's option and unit vocabulary are resolved: from the key's own definition, with
 * no data object, and with every way a provider can fail landing on a coded reason instead of
 * leaving the service.
 *
 * @internal
 */
final class KeyOptionsResolverTest extends Unit
{
    private const string PROVIDER = 'StaticProvider';

    private const array OWN_OPTIONS = [['key' => 'B', 'value' => 'b'], ['key' => 'A', 'value' => 'a']];

    private const string STATIC_PROVIDER_DEFINITION = '{"optionsProviderType":"class","optionsProviderClass":"StaticProvider"}';

    /**
     * @var array<string, object|null>
     */
    private array $providers = [];

    private int $serviceCalls = 0;

    private KeyOptionsResolver $resolver;

    public function _before(): void
    {
        $this->providers = [];
        $this->serviceCalls = 0;

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getFieldDefinitionFromJson')->willReturnCallback(
            function (array $definition, string $type): ?Data {
                ++$this->serviceCalls;

                return $this->buildDefinition($definition, $type);
            }
        );

        $providerResolver = $this->createMock(OptionsProviderResolverInterface::class);
        $providerResolver->method('resolveProvider')->willReturnCallback(
            fn (?string $class): ?object => $this->providers[(string) $class] ?? null
        );

        $this->resolver = new KeyOptionsResolver($serviceResolver, $providerResolver, new SelectOptionHydrator());
    }

    public function testAProviderReportingStaticOptionsPublishesThemInItsOwnOrder(): void
    {
        $this->providers[self::PROVIDER] = $this->provider(true, self::OWN_OPTIONS);

        $key = $this->key('select', self::STATIC_PROVIDER_DEFINITION);

        $this->assertSame(self::OWN_OPTIONS, $this->asArrays($this->resolver->getOptions($key)));
        $this->assertNull($this->resolver->getOptionsUnavailableReason($key));
    }

    public function testTheProviderIsAskedWithNoDataObject(): void
    {
        $provider = $this->createMock(SelectOptionsProviderInterface::class);
        $provider->method('hasStaticOptions')->willReturn(true);
        $provider->expects($this->once())->method('getOptions')->with(
            $this->callback(static fn (array $context): bool => $context['object'] === null),
            $this->isInstanceOf(Select::class)
        )->willReturn([]);
        $this->providers[self::PROVIDER] = $provider;

        $this->assertSame([], $this->resolver->getOptions($this->key('select', self::STATIC_PROVIDER_DEFINITION)));
    }

    public function testAMultiselectKeyIsResolvedLikeASelectKey(): void
    {
        $this->providers[self::PROVIDER] = $this->provider(true, self::OWN_OPTIONS);

        $key = $this->key('multiselect', self::STATIC_PROVIDER_DEFINITION);

        $this->assertSame(self::OWN_OPTIONS, $this->asArrays($this->resolver->getOptions($key)));
    }

    public function testANonStaticProviderPublishesNothingAndIsNeverAskedForOptions(): void
    {
        $provider = $this->createMock(SelectOptionsProviderInterface::class);
        $provider->method('hasStaticOptions')->willReturn(false);
        $provider->expects($this->never())->method('getOptions');
        $this->providers[self::PROVIDER] = $provider;

        $key = $this->key('select', self::STATIC_PROVIDER_DEFINITION);

        $this->assertNull($this->resolver->getOptions($key));
        $this->assertSame(
            OptionsUnavailableReason::PROVIDER_NOT_STATIC,
            $this->resolver->getOptionsUnavailableReason($key)
        );
    }

    /**
     * @dataProvider failingProviderProvider
     */
    public function testAProviderThatCannotBeAskedPublishesResolutionFailed(?object $provider): void
    {
        $this->providers[self::PROVIDER] = $provider;

        $key = $this->key('select', self::STATIC_PROVIDER_DEFINITION);

        $this->assertNull($this->resolver->getOptions($key));
        $this->assertSame(
            OptionsUnavailableReason::RESOLUTION_FAILED,
            $this->resolver->getOptionsUnavailableReason($key)
        );
    }

    /**
     * @return array<string, array{0: ?object}>
     */
    public static function failingProviderProvider(): array
    {
        $deleted = new class() extends AbstractThrowingProvider {
            protected function failure(): Throwable
            {
                return new Exception('Missing select options configuration 7');
            }
        };
        $typeError = new class() extends AbstractThrowingProvider {
            protected function failure(): Throwable
            {
                return new TypeError('wrong argument');
            }
        };

        return [
            'a deleted select options configuration' => [$deleted],
            'a provider raising a TypeError' => [$typeError],
            'a class that is not a select options provider' => [new stdClass()],
            'a class that does not resolve' => [null],
        ];
    }

    public function testAMalformedProviderOptionFailsTheMappingNotTheRow(): void
    {
        $this->providers[self::PROVIDER] = $this->provider(true, [['key' => 'A']]);

        $key = $this->key('select', self::STATIC_PROVIDER_DEFINITION);

        $this->assertNull($this->resolver->getOptions($key));
        $this->assertSame(
            OptionsUnavailableReason::RESOLUTION_FAILED,
            $this->resolver->getOptionsUnavailableReason($key)
        );
    }

    public function testAKeyThatIsNotSelectLikePublishesNothingAndConsultsNoProvider(): void
    {
        $key = $this->key('input', '{}');

        $this->assertNull($this->resolver->getOptions($key));
        $this->assertNull($this->resolver->getOptionsUnavailableReason($key));
        $this->assertNull($this->resolver->getUnits($key));
    }

    public function testAWrapperTypedKeyDoesNotLookInsideItsDelegate(): void
    {
        $this->providers[self::PROVIDER] = $this->createMock(SelectOptionsProviderInterface::class);
        $this->providers[self::PROVIDER]->expects($this->never())->method($this->anything());

        $key = $this->key('encryptedField', self::STATIC_PROVIDER_DEFINITION);

        $this->assertNull($this->resolver->getOptions($key));
        $this->assertNull($this->resolver->getOptionsUnavailableReason($key));
    }

    public function testASelectKeyNamingNoProviderPublishesItsOwnOptionsInTheirOrder(): void
    {
        $key = $this->key('select', (string) json_encode(['options' => self::OWN_OPTIONS]));

        $this->assertSame(self::OWN_OPTIONS, $this->asArrays($this->resolver->getOptions($key)));
        $this->assertNull($this->resolver->getOptionsUnavailableReason($key));
    }

    public function testASelectKeyWithNoStoredOptionsPublishesAnEmptyListAndNoReason(): void
    {
        $key = $this->key('select', '{}');

        $this->assertSame([], $this->resolver->getOptions($key));
        $this->assertNull($this->resolver->getOptionsUnavailableReason($key));
    }

    public function testAQuantityValueKeyPublishesItsUnitsInTheirOrder(): void
    {
        $key = $this->key('quantityValue', '{"defaultUnit":"kg","validUnits":["kg","g","lb"]}');

        $units = $this->resolver->getUnits($key);

        $this->assertSame('kg', $units?->getDefaultUnit());
        $this->assertSame(['kg', 'g', 'lb'], $units?->getValidUnits());
    }

    public function testAQuantityValueKeyRestrictingNothingPublishesNullValidUnits(): void
    {
        $units = $this->resolver->getUnits($this->key('quantityValue', '{"defaultUnit":"kg"}'));

        $this->assertNotNull($units);
        $this->assertNull($units->getValidUnits());
    }

    /**
     * @dataProvider unitCarryingTypeProvider
     */
    public function testTheUnitVocabularyIsReadOffTheDefinitionRatherThanATypeList(string $type): void
    {
        $units = $this->resolver->getUnits($this->key($type, '{"defaultUnit":"m","validUnits":["m","cm"]}'));

        $this->assertSame('m', $units?->getDefaultUnit());
        $this->assertSame(['m', 'cm'], $units?->getValidUnits());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unitCarryingTypeProvider(): array
    {
        return [
            'quantityValueRange' => ['quantityValueRange'],
            'inputQuantityValue' => ['inputQuantityValue'],
        ];
    }

    public function testTheDefinitionIsRebuiltPerBlobAndMemoisedUntilReset(): void
    {
        $this->providers[self::PROVIDER] = $this->provider(true, self::OWN_OPTIONS);
        $first = $this->key('select', self::STATIC_PROVIDER_DEFINITION);
        $second = $this->key('select', self::STATIC_PROVIDER_DEFINITION);

        $this->resolver->getOptions($first);
        $this->resolver->getUnits($first);
        $this->resolver->getOptions($second);
        $this->assertSame(1, $this->serviceCalls);

        $this->resolver->reset();
        $this->resolver->getOptions($first);
        $this->assertSame(2, $this->serviceCalls);
    }

    public function testAKeyConfigThatWasNeverHydratedPublishesNothing(): void
    {
        $keyConfig = new KeyConfig();

        $this->assertFalse((new ReflectionProperty($keyConfig, 'type'))->isInitialized($keyConfig));
        $this->assertNull($this->resolver->getOptions($keyConfig));
        $this->assertNull($this->resolver->getUnits($keyConfig));
    }

    /**
     * @param array<int, array<string, string>> $options
     */
    private function provider(bool $static, array $options): SelectOptionsProviderInterface
    {
        $provider = $this->createMock(SelectOptionsProviderInterface::class);
        $provider->method('hasStaticOptions')->willReturn($static);
        $provider->method('getOptions')->willReturn($options);

        return $provider;
    }

    /**
     * @param SelectOption[]|null $options
     *
     * @return array<int, array<string, mixed>>
     */
    private function asArrays(?array $options): array
    {
        return array_map(
            static fn (SelectOption $option): array => ['key' => $option->getKey(), 'value' => $option->getValue()],
            $options ?? []
        );
    }

    private function key(string $type, string $definition): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('k');
        $keyConfig->setType($type);
        $keyConfig->setDefinition($definition);

        return $keyConfig;
    }

    /**
     * @param array<string, mixed> $blob
     */
    private function buildDefinition(array $blob, string $type): ?Data
    {
        $definition = match ($type) {
            'select' => new Select(),
            'multiselect' => new Multiselect(),
            'quantityValue' => new QuantityValue(),
            'quantityValueRange' => new QuantityValueRange(),
            'inputQuantityValue' => new InputQuantityValue(),
            'encryptedField' => new EncryptedField(),
            default => new Input(),
        };

        $definition->setName('k');

        if ($definition instanceof Select || $definition instanceof Multiselect) {
            $definition->setOptions($blob['options'] ?? []);
            $definition->setOptionsProviderType($blob['optionsProviderType'] ?? null);
            $definition->setOptionsProviderClass($blob['optionsProviderClass'] ?? null);
        }

        if ($definition instanceof QuantityValue
            || $definition instanceof QuantityValueRange
            || $definition instanceof InputQuantityValue) {
            $definition->setDefaultUnit($blob['defaultUnit'] ?? null);
            $definition->setValidUnits($blob['validUnits'] ?? []);
        }

        if ($definition instanceof EncryptedField) {
            $delegate = new Select();
            $delegate->setOptionsProviderType('class');
            $delegate->setOptionsProviderClass($blob['optionsProviderClass'] ?? null);
            $definition->setDelegate($delegate);
        }

        return $definition;
    }
}
