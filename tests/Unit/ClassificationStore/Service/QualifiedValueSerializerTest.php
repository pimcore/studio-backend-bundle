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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\EngineCapabilityGuard;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValue;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializer;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function array_map;
use function json_encode;

/**
 * The three rejected writes a client has to tell apart carry three distinct `errorKey` values in the
 * error body - the member {@see \Pimcore\Bundle\StudioBackendBundle\EventSubscriber\ApiExceptionSubscriber}
 * puts there - rather than three distinct status codes or a response convention of their own.
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
        $keyQualifierResolver = new KeyQualifierResolver();
        $this->serializer = new QualifiedValueSerializer(
            $keyQualifierResolver,
            new QualifiedValueParser($keyQualifierResolver),
            new EngineCapabilityGuard()
        );
    }

    /**
     * An entry with no qualifiers is emitted without `var` and without `p`: an absent variant means
     * the default variant and an absent position means position 0.
     */
    public function testAnEntryWithoutQualifiersIsEmittedWithoutThem(): void
    {
        $payload = $this->serializer->toPayload([
            new QualifiedValue('80'),
            new QualifiedValue('176', self::IMPERIAL),
            new QualifiedValue('81', null, 1),
            new QualifiedValue('177', self::IMPERIAL, 1),
        ]);

        $this->assertSame(
            [
                ['v' => '80'],
                ['v' => '176', 'var' => self::IMPERIAL],
                ['v' => '81', 'p' => 1],
                ['v' => '177', 'var' => self::IMPERIAL, 'p' => 1],
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

        $this->assertSame(
            [[self::METRIC, 0], [self::IMPERIAL, 0], [null, 0]],
            $this->describe($values)
        );
        $this->assertSame(['80', '176', '81'], array_map(
            static fn (QualifiedValue $value): mixed => $value->getValue(),
            $values
        ));
    }

    /**
     * A client that has not been updated sends the value itself, which is position 0 of the default
     * variant - the slot core's single-value path addresses.
     */
    public function testAScalarIsOneEntryOfTheDefaultVariant(): void
    {
        $values = $this->serializer->parseEntries('80', $this->createKeyConfig());

        $this->assertSame([[null, 0]], $this->describe($values));
        $this->assertSame('80', $values[0]->getValue());
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

        $this->assertSame([[null, 0]], $this->describe($values));
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
            'an empty list' => [[]],
            'a keyed array' => [['value' => '80']],
            'null' => [null],
            // a legitimate value PHP would otherwise make disappear
            'the string zero' => ['0'],
            'the integer zero' => [0],
            'the empty string' => [''],
            'false' => [false],
        ];
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
     * Two entries that resolve to the same (variant, position) once the absent qualifiers normalise
     * to the default are refused, by an error key of their own.
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
            'both qualifiers absent' => [[['v' => '80'], ['v' => '81']]],
            'an absent position against an explicit 0' => [[['v' => '80'], ['v' => '81', 'p' => 0]]],
            'an absent variant against an explicit null' => [[['v' => '80'], ['v' => '81', 'var' => null]]],
            'the same variant at the same position' => [[
                ['v' => '80', 'var' => self::METRIC, 'p' => 1],
                ['v' => '81', 'var' => self::METRIC, 'p' => 1],
            ]],
        ];
    }

    /**
     * Two entries whose variant names differ only in a way a key identity could collapse stay two
     * slots: the position leads the identity and is always an integer, so nothing after the
     * separator is read as anything but the name itself.
     */
    public function testTwoVariantNamesThatCouldCollapseIntoOneKeyStayTwoSlots(): void
    {
        $keyConfig = $this->createKeyConfig(['a', 'a:0', ':0']);

        $values = $this->serializer->parseEntries(
            [['v' => '1', 'var' => 'a'], ['v' => '2', 'var' => 'a:0'], ['v' => '3', 'var' => ':0']],
            $keyConfig
        );

        $this->assertSame([['a', 0], ['a:0', 0], [':0', 0]], $this->describe($values));
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
            'an object whose position is a string' => [['v' => '80', 'p' => '1']],
        ];
    }

    /**
     * @dataProvider badPositionProvider
     */
    public function testAPositionThatIsNotANonNegativeIntegerIsRefused(mixed $position): void
    {
        $this->assertRefused(
            HttpResponseErrorKeys::INVALID_ARGUMENT,
            fn (): array => $this->serializer->parseEntries(
                [['v' => '80', 'p' => $position]],
                $this->createKeyConfig()
            )
        );
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function badPositionProvider(): array
    {
        return [
            'a numeric string' => ['1'],
            'a float' => [1.5],
            'a negative position' => [-1],
            'a boolean' => [true],
        ];
    }

    /**
     * A write that needs a capability the store's engine does not report is refused, by the third
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
        $repeated = [new QualifiedValue('80'), new QualifiedValue('81', null, 1)];

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
            'a second position on an engine reporting nothing' => [$repeated, [], true],
            'a second position on an engine reporting Variants only' => [
                $repeated,
                [StoreEngineResolverInterface::FEATURE_VARIANTS],
                true,
            ],
            'a second position on an engine reporting MultiValue' => [
                $repeated,
                [StoreEngineResolverInterface::FEATURE_MULTI_VALUE],
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
     * Both qualifiers are asserted, not only the one that was found open, and so is the sibling case
     * that must stay open: a write needing no capability still succeeds against null.
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

        $this->assertRefused(
            HttpResponseErrorKeys::ENGINE_CAPABILITY_MISSING,
            fn (): mixed => $this->serializer->assertSupported([new QualifiedValue('81', null, 1)], null)
        );
    }

    /**
     * A value stored before the key declared anything is the single entry of the default variant at
     * position 0, rather than being read as a list.
     */
    public function testAStoredSingleValueIsReadAsTheDefaultSlot(): void
    {
        $values = $this->serializer->readEntries('80');

        $this->assertSame([[null, 0]], $this->describe($values));
        $this->assertSame('80', $values[0]->getValue());
    }

    /**
     * A stored qualifier of the wrong shape falls back to the default rather than failing the read:
     * it is data this bundle wrote, not a request.
     */
    public function testAStoredQualifierOfTheWrongShapeFallsBackToTheDefault(): void
    {
        $values = $this->serializer->readEntries([
            ['v' => '80', 'var' => self::METRIC, 'p' => 2],
            ['v' => '81', 'var' => 17, 'p' => '3'],
        ]);

        $this->assertSame([[self::METRIC, 2], [null, 0]], $this->describe($values));
    }

    /**
     * Reading what is stored is strict about the shape of the list itself, so that it never drops
     * anything: an array of which only some elements are entries is not the list this bundle wrote,
     * and is read whole as one value rather than having its other elements silently skipped.
     *
     * {@see QualifiedValueSerializer::parseEntries()} reads a *supplied* payload the other way
     * round - any entry makes it a list, and the rest is then refused - because there a malformed
     * element has to reach the validator instead of being coerced away.
     */
    public function testAStoredArrayThatIsOnlyPartlyEntriesIsReadWholeAndDropsNothing(): void
    {
        $stored = [['v' => '80'], ['var' => self::IMPERIAL]];

        $values = $this->serializer->readEntries($stored);

        $this->assertSame([[null, 0]], $this->describe($values));
        $this->assertSame($stored, $values[0]->getValue());
    }

    /**
     * The one value a consumer holding a single cell shows, for each of the three shapes a slot can
     * hold. A slot that is not the entry list comes back unchanged, so such a consumer can reduce
     * every slot through this and only the lists change.
     *
     * @dataProvider defaultSlotProvider
     */
    public function testTheDefaultSlotIsTheOneValueASingleCellShows(mixed $stored, mixed $expected): void
    {
        $this->assertSame(
            $expected,
            $this->serializer->getDefaultSlotValue($stored, $this->createKeyConfig())
        );
    }

    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function defaultSlotProvider(): array
    {
        return [
            'the entry list, which carries the default slot' => [
                [['v' => '80'], ['v' => '176', 'var' => self::IMPERIAL]],
                '80',
            ],
            'the entry list, where the default slot is not first' => [
                [['v' => '176', 'var' => self::IMPERIAL], ['v' => '80']],
                '80',
            ],
            'the entry list, holding no default slot at all' => [
                [['v' => '176', 'var' => self::IMPERIAL]],
                '176',
            ],
            'the entry list, where only a hidden entry is not the default slot' => [
                [['v' => '9', 'var' => 'nautical']],
                null,
            ],
            // every value of every key that declares nothing comes back untouched
            'a bare value' => ['80', '80'],
            'a multi select value' => [['red', 'green'], ['red', 'green']],
            'a relation value' => [[['type' => 'object', 'id' => 7]], [['type' => 'object', 'id' => 7]]],
            'an empty slot' => [null, null],
            // legitimate values PHP would otherwise make disappear
            'the string zero' => ['0', '0'],
            'the integer zero' => [0, 0],
            'the empty string' => ['', ''],
            'false' => [false, false],
            'an empty array' => [[], []],
        ];
    }

    /**
     * The sibling of the variant guard: a key that does not declare itself repeatable holds one
     * value per variant, so a position other than the first is a qualifier it does not permit and
     * is refused by its own error key rather than stored.
     *
     * @dataProvider notRepeatableProvider
     *
     * @param array<string, mixed> $definition
     */
    public function testAPositionOnAKeyThatIsNotRepeatableIsRefused(array $definition): void
    {
        $keyConfig = $this->createKeyConfigFrom($definition);

        $this->assertRefused(
            HttpResponseErrorKeys::KEY_NOT_REPEATABLE,
            fn (): array => $this->serializer->parseEntries(
                [['v' => '80'], ['v' => '81', 'p' => 1]],
                $keyConfig
            )
        );

        // and the first position is still accepted on the very same key
        $this->assertSame(
            [[null, 0], [self::IMPERIAL, 0]],
            $this->describe($this->serializer->parseEntries(
                [['v' => '80'], ['v' => '176', 'var' => self::IMPERIAL]],
                $keyConfig
            ))
        );
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function notRepeatableProvider(): array
    {
        return [
            'multiValue declared false' => [['variants' => [self::METRIC, self::IMPERIAL], 'multiValue' => false]],
            'multiValue not declared at all' => [['variants' => [self::METRIC, self::IMPERIAL]]],
        ];
    }

    /**
     * The merge matches by qualifier and never by position in the list, so a write naming one slot
     * leaves every other entry of the key as it was.
     */
    public function testTheMergeMatchesByQualifierAndKeepsTheRest(): void
    {
        $merged = $this->serializer->mergeEntries(
            [
                new QualifiedValue('80'),
                new QualifiedValue('81', null, 1),
                new QualifiedValue('82', null, 2),
            ],
            [new QualifiedValue('99', null, 1)]
        );

        $this->assertSame([[null, 0], [null, 1], [null, 2]], $this->describe($merged));
        $this->assertSame(['80', '99', '82'], array_map(
            static fn (QualifiedValue $value): mixed => $value->getValue(),
            $merged
        ));
    }

    /**
     * The declaration decides, whatever the row holds: a variant the declaration no longer names is
     * refused on a write - there is no leniency for the entry a row already carries - because a
     * read does not publish it, so no client saving back what it was handed names it.
     */
    public function testAVariantTheDeclarationNoLongerNamesIsRefusedEvenWhereTheRowHoldsIt(): void
    {
        $keyConfig = $this->createKeyConfigFrom(['variants' => [self::METRIC], 'multiValue' => true]);

        $this->assertRefused(
            HttpResponseErrorKeys::UNDECLARED_VARIANT,
            fn (): array => $this->serializer->parseEntries(
                [['v' => '176', 'var' => self::IMPERIAL]],
                $keyConfig
            )
        );
    }

    /**
     * The sibling, on the other qualifier: a position past the first is refused for a key that is
     * not repeatable, by its own error key, whatever the row holds.
     */
    public function testAPositionTheDeclarationNoLongerPermitsIsRefusedEvenWhereTheRowHoldsIt(): void
    {
        $this->assertRefused(
            HttpResponseErrorKeys::KEY_NOT_REPEATABLE,
            fn (): array => $this->serializer->parseEntries(
                [['v' => '81', 'p' => 1]],
                $this->createKeyConfigFrom(['variants' => [self::METRIC]])
            )
        );
    }

    /**
     * A stored entry is visible exactly when a written one would be accepted, and every entry is one
     * or the other: narrowing hides entries and drops none, whichever qualifier it narrows.
     *
     * @dataProvider narrowedDeclarationProvider
     *
     * @param array<string, mixed> $definition
     * @param array<int, array{0: ?string, 1: int}> $visible
     * @param array<int, array{0: ?string, 1: int}> $hidden
     */
    public function testNarrowingHidesEntriesAndLosesNone(array $definition, array $visible, array $hidden): void
    {
        $keyConfig = $this->createKeyConfigFrom($definition);
        $stored = [
            ['v' => '80'],
            ['v' => '176', 'var' => self::IMPERIAL],
            ['v' => '81', 'p' => 1],
        ];

        $this->assertSame($visible, $this->describe($this->serializer->getVisibleEntries($stored, $keyConfig)));
        $this->assertSame($hidden, $this->describe($this->serializer->getHiddenEntries($stored, $keyConfig)));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<int, mixed>, 2: array<int, mixed>}>
     */
    public static function narrowedDeclarationProvider(): array
    {
        return [
            'the declaration still permits everything' => [
                ['variants' => [self::METRIC, self::IMPERIAL], 'multiValue' => true],
                [[null, 0], [self::IMPERIAL, 0], [null, 1]],
                [],
            ],
            'a variant has left the declaration' => [
                ['variants' => [self::METRIC], 'multiValue' => true],
                [[null, 0], [null, 1]],
                [[self::IMPERIAL, 0]],
            ],
            'the key has ceased to be repeatable' => [
                ['variants' => [self::IMPERIAL]],
                [[null, 0], [self::IMPERIAL, 0]],
                [[null, 1]],
            ],
            'the key declares nothing any more' => [
                ['fieldtype' => 'input'],
                [[null, 0]],
                [[self::IMPERIAL, 0], [null, 1]],
            ],
        ];
    }

    /**
     * What a slot holds decides its shape as much as the key's declaration does, so the predicate
     * the object data path asks and the read that follows it are asserted over one set of fixtures
     * rather than apart: a slot that holds entries reads as its entries, and one that does not reads
     * whole as the single value it is.
     *
     * @dataProvider slotShapeProvider
     */
    public function testThePredicateAndTheReadAgreeOnWhatASlotHolds(
        mixed $slot,
        bool $holds,
        int $entries
    ): void {
        $this->assertSame($holds, $this->serializer->holdsEntries($slot));
        $this->assertCount($entries, $this->serializer->readEntries($slot));
    }

    /**
     * @return array<string, array{0: mixed, 1: bool, 2: int}>
     */
    public static function slotShapeProvider(): array
    {
        return [
            'the list this bundle writes' => [[['v' => '80'], ['v' => '176', 'var' => self::IMPERIAL]], true, 2],
            'a list of one entry' => [[['v' => '80']], true, 1],
            'a bare value' => ['80', false, 1],
            'a multi select value' => [['red', 'green'], false, 1],
            'a relation value' => [[['type' => 'object', 'id' => 7]], false, 1],
            'an array only partly entries' => [[['v' => '80'], ['var' => self::IMPERIAL]], false, 1],
            'an empty slot' => [null, false, 1],
            'an empty array' => [[], false, 1],
            // legitimate values PHP would otherwise make disappear
            'the string zero' => ['0', false, 1],
            'the integer zero' => [0, false, 1],
            'the empty string' => ['', false, 1],
            'false' => [false, false, 1],
        ];
    }

    /**
     * An entry the stored list does not hold is appended rather than dropped.
     */
    public function testTheMergeAppendsASlotTheStoredListDoesNotHold(): void
    {
        $merged = $this->serializer->mergeEntries(
            [new QualifiedValue('80')],
            [new QualifiedValue('176', self::IMPERIAL)]
        );

        $this->assertSame([[null, 0], [self::IMPERIAL, 0]], $this->describe($merged));
    }

    /**
     * @param QualifiedValue[] $values
     *
     * @return array<int, array{0: ?string, 1: int}>
     */
    private function describe(array $values): array
    {
        return array_map(
            static fn (QualifiedValue $value): array => [$value->getVariant(), $value->getPosition()],
            $values
        );
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
    private function createKeyConfig(array $variants = [self::METRIC, self::IMPERIAL]): KeyConfig
    {
        return $this->createKeyConfigFrom(['variants' => $variants, 'multiValue' => true]);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function createKeyConfigFrom(array $definition): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');
        $keyConfig->setDefinition((string) json_encode($definition));

        return $keyConfig;
    }
}
