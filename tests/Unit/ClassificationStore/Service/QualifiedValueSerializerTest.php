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

use Codeception\Stub;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\EngineCapabilityGuard;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValue;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializer;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Exception\UnsupportedEngineFeatureException;
use function array_map;
use function json_encode;

/**
 * The rejected writes a client has to tell apart carry distinct `errorKey` values in the error body
 * - the member {@see \Pimcore\Bundle\StudioBackendBundle\EventSubscriber\ApiExceptionSubscriber}
 * puts there - rather than distinct status codes or a response convention of their own.
 *
 * @internal
 */
final class QualifiedValueSerializerTest extends Unit
{
    private const string METRIC = 'metric';

    private const string IMPERIAL = 'imperial';

    private const int UNPROCESSABLE = 422;

    private QualifiedValueSerializer $serializer;

    public function _before(): void
    {
        // the real collaborators: the parse and the engine verdict are reached through the
        // serializer, and what this suite asserts is what a caller of it gets
        $keyQualifierResolver = new KeyQualifierResolver(new MultiValueServiceResolver());
        $this->serializer = new QualifiedValueSerializer(
            $keyQualifierResolver,
            new QualifiedValueParser($keyQualifierResolver),
            new EngineCapabilityGuard()
        );
    }

    /**
     * An entry of the default variant is emitted without `var`, and no entry carries `p`.
     */
    public function testAnEntryIsEmittedWithItsVariantAndNoPosition(): void
    {
        $payload = $this->serializer->toPayload([
            new QualifiedValue('80'),
            new QualifiedValue('176', self::IMPERIAL),
        ]);

        $this->assertSame(
            [
                ['v' => '80'],
                ['v' => '176', 'var' => self::IMPERIAL],
            ],
            $payload
        );
    }

    public function testASuppliedListIsParsedIntoItsEntries(): void
    {
        $values = $this->serializer->parseEntries(
            [
                ['v' => '80', 'var' => self::METRIC],
                ['v' => '176', 'var' => self::IMPERIAL],
                ['v' => '81'],
            ],
            $this->createKeyConfig()
        );

        $this->assertSame([self::METRIC, self::IMPERIAL, null], $this->describe($values));
        $this->assertSame(['80', '176', '81'], array_map(
            static fn (QualifiedValue $value): mixed => $value->getValue(),
            $values
        ));
    }

    /**
     * A client that has not been updated sends the value itself, which is the default variant - the
     * slot core's single-value path addresses.
     */
    public function testAScalarIsOneEntryOfTheDefaultVariant(): void
    {
        $values = $this->serializer->parseEntries('80', $this->createKeyConfig());

        $this->assertSame([null], $this->describe($values));
        $this->assertSame('80', $values[0]->getValue());
    }

    /**
     * A grid cell names the variant it addresses by sending its entry alone, as an object.
     */
    public function testACellSendsItsEntryAloneAndNamesItsVariant(): void
    {
        $values = $this->serializer->parseEntries(['v' => '60', 'var' => self::METRIC], $this->createKeyConfig());

        $this->assertSame([self::METRIC], $this->describe($values));
        $this->assertSame('60', $values[0]->getValue());
    }

    /**
     * An older client still sends `p`, whatever it holds; it is not read, so the entry addresses its
     * variant's one value and the two halves of the change can ship in either order.
     *
     * @dataProvider legacyPositionProvider
     */
    public function testAPositionAnOlderClientSendsIsAcceptedAndNotRead(mixed $position): void
    {
        $values = $this->serializer->parseEntries(
            [['v' => '80', 'p' => $position], ['v' => '60', 'var' => self::METRIC, 'p' => $position]],
            $this->createKeyConfig()
        );

        $this->assertSame([null, self::METRIC], $this->describe($values));
        $this->assertSame([['v' => '80'], ['v' => '60', 'var' => self::METRIC]], $this->serializer->toPayload($values));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function legacyPositionProvider(): array
    {
        return [
            'the first position' => [0],
            'a later position' => [1],
            'a malformed position' => ['1'],
        ];
    }

    /**
     * The array value of a multi-select or a relation field is not a list of entries, so a key that
     * declares qualifiers and holds such a type keeps storing its value whole.
     *
     * @dataProvider notAnEntryListProvider
     */
    public function testAValueThatIsNotAListOfEntriesIsOneEntry(mixed $payload): void
    {
        $values = $this->serializer->parseEntries($payload, $this->createKeyConfig());

        $this->assertSame([null], $this->describe($values));
        $this->assertSame($payload, $values[0]->getValue());
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function notAnEntryListProvider(): array
    {
        return [
            'a multi select value' => [['red', 'green']],
            'a relation value' => [[['type' => 'object', 'id' => 7]]],
            'a keyed array' => [['value' => '80']],
            // null is NOT here: it is the absence of a value, not one, and it is what the editor
            // sends for a key it holds nothing at. Parsed as a value it addresses the DEFAULT
            // variant, which a key declaring only named variants never declares, so core refuses
            // the write and the whole object fails to save
            // ({@see self::testNullIsNoEntryRatherThanOneEntryOfTheDefaultVariant()}).
            // a legitimate value PHP would otherwise make disappear
            'the string zero' => ['0'],
            'the integer zero' => [0],
            'the empty string' => [''],
            'false' => [false],
        ];
    }

    /**
     * The empty list is the empty *entry* list and not a value: no entry at all.
     *
     * It is what a read of a key whose declaration narrowed to hide every entry of its row
     * publishes, so reading it back as one entry holding the empty array would have a PUT of what
     * that read handed out manufacture a default entry the row never held - and hand the key's own
     * scalar adapter an array. The empty array as a *value* of the default slot is still writable,
     * by naming the slot it belongs to, which is the sibling assertion here.
     *
     * The empty list never reaches this parse for a key that declares nothing: such a key's payload
     * is one value whatever it looks like, so its own stored empty array is untouched by this.
     */
    /**
     * A key the payload names but holds no value at carries null, and that is the same statement as
     * the empty list: the key holds nothing. Read as a value it would instead become one entry of
     * the DEFAULT variant - which a key declaring only named variants never declares, so core
     * refuses the whole write and the object cannot be saved at all.
     */
    public function testNullIsNoEntryRatherThanOneEntryOfTheDefaultVariant(): void
    {
        $this->assertSame([], $this->serializer->parseEntries(null, $this->createKeyConfig()));
    }

    public function testTheEmptyListIsNoEntryRatherThanOneEntryHoldingIt(): void
    {
        $this->assertSame([], $this->serializer->parseEntries([], $this->createKeyConfig()));

        $named = $this->serializer->parseEntries([['v' => []]], $this->createKeyConfig());

        $this->assertSame([null], $this->describe($named));
        $this->assertSame([], $named[0]->getValue());
    }

    /**
     * An entry naming a variant the key does not declare is refused, by its own error key.
     *
     * @dataProvider undeclaredVariantProvider
     */
    public function testAnEntryNamingAnUndeclaredVariantIsRefused(mixed $variant): void
    {
        $this->assertRefused(
            HttpResponseErrorKeys::UNDECLARED_VARIANT,
            fn (): array => $this->serializer->parseEntries(
                [['v' => '80', 'var' => $variant]],
                $this->createKeyConfig()
            )
        );
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function undeclaredVariantProvider(): array
    {
        return [
            'a name the key does not declare' => ['nautical'],
            'a name that is not a string' => [17],
            // never the default variant: the declaration cannot carry an empty name
            'the empty name' => [''],
        ];
    }

    /**
     * A value addressed to the default slot of a key that declares named variants and permits no
     * unqualified value is refused here, by the error key a named variant the key does not declare
     * is refused with.
     *
     * Core refuses the same write ({@see Classificationstore::setKeyValues()} raises
     * `InvalidConfigException` for the variant it spells "default"), and reached there it is wrapped
     * as a database failure: a 500 that fails the save of every other field and key of the object
     * with it, for a request naming a slot the key does not have.
     *
     * @dataProvider forbiddenDefaultSlotProvider
     */
    public function testAValueOfADefaultSlotTheKeyForbidsIsRefused(mixed $payload): void
    {
        $this->assertRefused(
            HttpResponseErrorKeys::UNDECLARED_VARIANT,
            fn (): array => $this->serializer->parseEntries(
                $payload,
                $this->createKeyConfig(permitsDefaultVariant: false)
            )
        );
    }

    /**
     * Every shape a payload addresses the default slot in: an entry that names no variant, one that
     * names it as null, and a value that is no list of entries at all.
     *
     * @return array<string, array{0: mixed}>
     */
    public static function forbiddenDefaultSlotProvider(): array
    {
        return [
            'an entry naming no variant' => [[['v' => '80']]],
            'an entry naming it as null' => [[['v' => '80', 'var' => null]]],
            'a cell sending its entry alone' => [['v' => '80']],
            'the bare value an un-updated client sends' => ['80'],
            'a value that is no list of entries' => [['red', 'green']],
        ];
    }

    /**
     * The guard above holds a write to the key's declaration and to nothing else: the same key still
     * takes a value of every variant it does declare.
     */
    public function testAKeyForbiddingTheDefaultSlotStillTakesItsDeclaredVariants(): void
    {
        $values = $this->serializer->parseEntries(
            [
                ['v' => '80', 'var' => self::METRIC],
                ['v' => '176', 'var' => self::IMPERIAL],
            ],
            $this->createKeyConfig(permitsDefaultVariant: false)
        );

        $this->assertSame([self::METRIC, self::IMPERIAL], $this->describe($values));
    }

    /**
     * The sibling of the case above, and what keeps the guard from reading the default slot as
     * forbidden on every key: a key that permits an unqualified value - which is what a key stating
     * nothing permits - still takes one, however the payload carries it.
     */
    public function testAKeyPermittingTheDefaultSlotStillTakesIt(): void
    {
        $entry = $this->serializer->parseEntries([['v' => '80']], $this->createKeyConfig());
        $bare = $this->serializer->parseEntries('80', $this->createKeyConfig());

        $this->assertSame([null], $this->describe($entry));
        $this->assertSame([null], $this->describe($bare));
    }

    /**
     * Two entries that resolve to the same variant once an absent variant normalises to the
     * default are refused, by an error key of their own - also where an older client told them
     * apart by `p`, since a variant holds one value.
     *
     * @dataProvider duplicateQualifierProvider
     *
     * @param array<int, array<string, mixed>> $payload
     */
    public function testTwoEntriesResolvingToOneSlotAreRefused(array $payload): void
    {
        $this->assertRefused(
            HttpResponseErrorKeys::DUPLICATE_VALUE_QUALIFIER,
            fn (): array => $this->serializer->parseEntries($payload, $this->createKeyConfig())
        );
    }

    /**
     * @return array<string, array{0: array<int, array<string, mixed>>}>
     */
    public static function duplicateQualifierProvider(): array
    {
        return [
            'both variants absent' => [[['v' => '80'], ['v' => '81']]],
            'an absent variant against an explicit null' => [[['v' => '80'], ['v' => '81', 'var' => null]]],
            'the same variant twice' => [[
                ['v' => '80', 'var' => self::METRIC],
                ['v' => '81', 'var' => self::METRIC],
            ]],
            'the same variant at two positions of an older client' => [[
                ['v' => '80', 'var' => self::METRIC, 'p' => 0],
                ['v' => '81', 'var' => self::METRIC, 'p' => 1],
            ]],
        ];
    }

    /**
     * Two variant names that differ only by characters a composed identity could collapse stay two
     * slots: the identity is the name itself.
     */
    public function testTwoVariantNamesThatCouldCollapseIntoOneKeyStayTwoSlots(): void
    {
        $keyConfig = $this->createKeyConfig(['a', 'a:0', ':0']);

        $values = $this->serializer->parseEntries(
            [['v' => '1', 'var' => 'a'], ['v' => '2', 'var' => 'a:0'], ['v' => '3', 'var' => ':0']],
            $keyConfig
        );

        $this->assertSame(['a', 'a:0', ':0'], $this->describe($values));
    }

    /**
     * @dataProvider malformedEntryProvider
     */
    public function testAMalformedEntryIsRefusedByTheValidatorAndNotByTheTypeSystem(mixed $entry): void
    {
        $this->assertRefused(
            HttpResponseErrorKeys::INVALID_ARGUMENT,
            fn (): array => $this->serializer->parseEntries(
                [['v' => '80', 'var' => self::METRIC], $entry],
                $this->createKeyConfig()
            )
        );
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function malformedEntryProvider(): array
    {
        return [
            'a scalar among entries' => ['80'],
            'an object without a value' => [['var' => self::IMPERIAL]],
        ];
    }

    /**
     * A write that needs a capability the store's engine does not report is refused, by its own
     * error key - and only such a write.
     *
     * @dataProvider capabilityProvider
     *
     * @param QualifiedValue[] $values
     * @param string[] $capabilities
     */
    public function testOnlyAWriteNeedingAMissingCapabilityIsRefused(
        array $values,
        array $capabilities,
        bool $refused
    ): void {
        if (!$refused) {
            $this->assertNull($this->refusalOf(fn (): mixed => $this->serializer
                ->assertSupported($values, $capabilities)));

            return;
        }

        $this->assertRefused(
            HttpResponseErrorKeys::ENGINE_CAPABILITY_MISSING,
            fn (): mixed => $this->serializer->assertSupported($values, $capabilities)
        );
    }

    /**
     * @return array<string, array{0: QualifiedValue[], 1: string[], 2: bool}>
     */
    public static function capabilityProvider(): array
    {
        $default = [new QualifiedValue('80')];
        $variant = [new QualifiedValue('80'), new QualifiedValue('176', self::IMPERIAL)];

        return [
            'the single default slot on an engine reporting nothing' => [$default, [], false],
            'a variant on an engine reporting nothing' => [$variant, [], true],
            'a variant on an engine reporting MultiValue only' => [
                $variant,
                [StoreEngineResolverInterface::FEATURE_MULTI_VALUE],
                true,
            ],
            'a variant on an engine reporting Variants' => [
                $variant,
                [StoreEngineResolverInterface::FEATURE_VARIANTS],
                false,
            ],
        ];
    }

    /**
     * A capability set of null is a core that could not be asked, and the gate is fail-closed on it:
     * a capability nobody confirmed is one the write does not get. This is the one answer
     * {@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolverInterface::getCapabilitiesOfStore()}
     * gives against the very core the gate exists to protect, so letting it through would skip every
     * capability check there.
     *
     * The sibling case that must stay open is asserted too: a write needing no capability still
     * succeeds against null.
     */
    public function testACoreThatCannotBeAskedRefusesAWriteNeedingACapability(): void
    {
        $this->assertNull($this->refusalOf(
            fn (): mixed => $this->serializer->assertSupported([new QualifiedValue('80')], null)
        ));

        $this->assertRefused(
            HttpResponseErrorKeys::ENGINE_CAPABILITY_MISSING,
            fn (): mixed => $this->serializer->assertSupported([new QualifiedValue('176', self::IMPERIAL)], null)
        );
    }

    /**
     * The stored values are core's: one `getKeyValues()` per slot, the default variant first and then
     * each declared variant in declared order, one value per variant.
     */
    public function testTheStoredEntriesAreReadSlotBySlotThroughCore(): void
    {
        $asked = [];
        $store = Stub::make(Classificationstore::class, [
            'getKeyValues' => static function (
                int $groupId,
                int $keyId,
                ?string $language = null,
                ?string $variant = null
            ) use (&$asked): array {
                $asked[] = [$groupId, $keyId, $language, $variant];

                return match ($variant) {
                    null => [0 => '80'],
                    self::IMPERIAL => [0 => '176'],
                    default => [],
                };
            },
        ]);

        $values = $this->serializer->readStoredEntries($store, 5, 7, 'en', $this->createKeyConfig());

        $this->assertSame(
            [[5, 7, 'en', null], [5, 7, 'en', self::METRIC], [5, 7, 'en', self::IMPERIAL]],
            $asked
        );
        $this->assertSame([null, self::IMPERIAL], $this->describe($values));
        $this->assertSame(['80', '176'], array_map(
            static fn (QualifiedValue $value): mixed => $value->getValue(),
            $values
        ));
    }

    /**
     * A row written while a variant could still repeat publishes the first value it holds there.
     */
    public function testAVariantHoldingMoreThanOneStoredValuePublishesTheFirst(): void
    {
        $store = Stub::make(Classificationstore::class, [
            'getKeyValues' => static fn (
                int $groupId,
                int $keyId,
                ?string $language = null,
                ?string $variant = null
            ): array => $variant === null ? [0 => '80', 1 => '81'] : [],
        ]);

        $values = $this->serializer->readStoredEntries($store, 5, 7, 'en', $this->createKeyConfig());

        $this->assertSame([null], $this->describe($values));
        $this->assertSame('80', $values[0]->getValue());
    }

    /**
     * An engine that cannot address a variant holds nothing there, so the read of the slots it can
     * address still answers.
     */
    public function testAVariantTheEngineCannotAddressHoldsNothing(): void
    {
        $store = Stub::make(Classificationstore::class, [
            'getKeyValues' => static function (
                int $groupId,
                int $keyId,
                ?string $language = null,
                ?string $variant = null
            ): array {
                if ($variant !== null) {
                    throw new UnsupportedEngineFeatureException(EngineFeature::Variants, $groupId, $keyId);
                }

                return [0 => '80'];
            },
        ]);

        $values = $this->serializer->readStoredEntries($store, 5, 7, 'default', $this->createKeyConfig());

        $this->assertSame([null], $this->describe($values));
    }

    /**
     * @dataProvider defaultSlotProvider
     *
     * @param array<int, array<string, mixed>>|mixed $payload
     */
    public function testTheDefaultSlotIsTheOneValueASingleCellShows(mixed $payload, mixed $expected): void
    {
        $this->assertSame($expected, $this->serializer->getDefaultSlotValue($payload));
    }

    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function defaultSlotProvider(): array
    {
        return [
            'the default variant wins over a variant listed first' => [
                [['v' => '176', 'var' => self::IMPERIAL], ['v' => '80']],
                '80',
            ],
            'the first value where there is no default' => [
                [['v' => '176', 'var' => self::IMPERIAL], ['v' => '80', 'var' => self::METRIC]],
                '176',
            ],
            'nothing is the empty cell' => [[], null],
            'no list is the empty cell' => [null, null],
        ];
    }

    /**
     * The merge matches by variant and never by position in the list, so a write naming one slot
     * leaves every other entry of the key as it was.
     */
    public function testTheMergeMatchesByQualifierAndKeepsTheRest(): void
    {
        $merged = $this->serializer->mergeEntries(
            [
                new QualifiedValue('80'),
                new QualifiedValue('60', self::METRIC),
                new QualifiedValue('176', self::IMPERIAL),
            ],
            [new QualifiedValue('99', self::METRIC)]
        );

        $this->assertSame([null, self::METRIC, self::IMPERIAL], $this->describe($merged));
        $this->assertSame(['80', '99', '176'], array_map(
            static fn (QualifiedValue $value): mixed => $value->getValue(),
            $merged
        ));
    }

    /**
     * @param QualifiedValue[] $values
     *
     * @return array<int, ?string>
     */
    private function describe(array $values): array
    {
        return array_map(static fn (QualifiedValue $value): ?string => $value->getVariant(), $values);
    }

    /**
     * @param callable(): mixed $write
     */
    private function assertRefused(HttpResponseErrorKeys $errorKey, callable $write): void
    {
        try {
            $write();
            $this->fail('Expected the write to be refused.');
        } catch (AbstractApiException $exception) {
            $this->assertSame($errorKey->value, $exception->getErrorKey());
            $this->assertSame(self::UNPROCESSABLE, $exception->getStatusCode());
        }
    }

    /**
     * The error key a write was refused with, or null where it was accepted.
     *
     * @param callable(): mixed $write
     */
    private function refusalOf(callable $write): ?string
    {
        try {
            $write();
        } catch (AbstractApiException $exception) {
            return $exception->getErrorKey();
        }

        return null;
    }

    /**
     * @param string[] $variants
     */
    private function createKeyConfig(
        array $variants = [self::METRIC, self::IMPERIAL],
        bool $permitsDefaultVariant = true
    ): KeyConfig {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');
        $keyConfig->setType(MultiValueServiceResolver::MULTI_VALUE_TYPE);
        $keyConfig->setDefinition(
            (string) json_encode(['variants' => $variants, 'unqualifiedValue' => $permitsDefaultVariant])
        );

        return $keyConfig;
    }
}
