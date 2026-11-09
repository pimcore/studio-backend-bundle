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
    public function testTheEmptyListIsNoEntryRatherThanOneEntryHoldingIt(): void
    {
        $this->assertSame([], $this->serializer->parseEntries([], $this->createKeyConfig()));

        $named = $this->serializer->parseEntries([['v' => []]], $this->createKeyConfig());

        $this->assertSame([[null, 0]], $this->describe($named));
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
        $values = $this->serializer->readEntries('80', $this->createKeyConfig());

        $this->assertSame([[null, 0]], $this->describe($values));
        $this->assertSame('80', $values[0]->getValue());
    }

    /**
     * A stored qualifier of the wrong shape falls back to the default rather than failing the read:
     * it is data this bundle wrote, not a request.
     */
    public function testAStoredQualifierOfTheWrongShapeFallsBackToTheDefault(): void
    {
        $values = $this->serializer->readEntries(
            self::storedSlot([
                ['v' => '80', 'var' => self::METRIC, 'p' => 2],
                ['v' => '81', 'var' => 17, 'p' => '3'],
            ]),
            $this->createKeyConfig()
        );

        $this->assertSame([[self::METRIC, 2], [null, 0]], $this->describe($values));
    }

    /**
     * Where the declaration says the slot may hold a list, the shape of that list is then read
     * strictly, so that the read never drops anything: an array of which only some elements are
     * entries is not what this bundle wrote, and the whole value is read as the one value it is
     * rather than having its other elements silently skipped.
     *
     * {@see QualifiedValueSerializer::parseEntries()} reads a *supplied* payload the other way
     * round - any entry makes it a list, and the rest is then refused - because there a malformed
     * element has to reach the validator instead of being coerced away.
     */
    public function testAStoredListThatIsOnlyPartlyEntriesIsReadWholeAndDropsNothing(): void
    {
        $stored = self::storedSlot([['v' => '80'], ['var' => self::IMPERIAL]]);

        $values = $this->serializer->readEntries($stored, $this->createKeyConfig());

        $this->assertSame([[null, 0]], $this->describe($values));
        $this->assertSame($stored, $values[0]->getValue());
    }

    /**
     * What a write stores is read back as the entries it stored, qualifiers and all - including an
     * entry whose own value is a list of maps carrying `v`, which the value of a table key whose
     * one column is named `v` is. The entry holding that table is told from the table itself by
     * where the list sits, and the list the key's declaration puts it in is the slot.
     */
    public function testWhatAWriteStoresIsReadBackAsTheEntriesItStored(): void
    {
        $table = [['v' => 'cell'], ['v' => 'other']];
        $entries = [
            new QualifiedValue($table),
            new QualifiedValue('176', self::IMPERIAL),
            new QualifiedValue('81', null, 1),
        ];

        $stored = $this->serializer->toPayload($entries);
        $read = $this->serializer->readEntries($stored, $this->createKeyConfig());

        $this->assertSame([[null, 0], [self::IMPERIAL, 0], [null, 1]], $this->describe($read));
        $this->assertSame([$table, '176', '81'], array_map(
            static fn (QualifiedValue $value): mixed => $value->getValue(),
            $read
        ));
        $this->assertSame($table, $this->serializer->getDefaultSlotValue($stored, $this->createKeyConfig()));
    }

    /**
     * The value of a key that declares nothing is never read as entries, however much it looks like
     * them: a table whose first column is named `v` stores a list of maps carrying `v` and comes
     * back as that table, not as its first cell.
     *
     * This is the read half of the rule that the declaration alone decides the shape. The key here
     * declares nothing, so there is nothing to read entries by and nothing to reduce by - and while
     * the shape of the value was what decided, this very value was read as two entries and
     * published as `80`.
     *
     * @dataProvider undeclaredSlotProvider
     */
    public function testAValueOfAKeyDeclaringNothingIsNeverReadAsEntries(mixed $stored): void
    {
        $keyConfig = $this->createKeyConfigFrom(['fieldtype' => 'table']);

        $this->assertFalse($this->serializer->holdsEntries($stored, $keyConfig));
        $this->assertSame($stored, $this->serializer->getDefaultSlotValue($stored, $keyConfig));
        $this->assertSame(
            [$stored],
            array_map(
                static fn (QualifiedValue $value): mixed => $value->getValue(),
                $this->serializer->readEntries($stored, $keyConfig)
            )
        );
        $this->assertSame([], $this->describe($this->serializer->getHiddenEntries($stored, $keyConfig)));
    }

    /**
     * Every shape that was read as entries while the value decided, plus the shapes that were not,
     * so that what this says is "no shape makes it entries" rather than "this one shape does not".
     *
     * @return array<string, array{0: mixed}>
     */
    public static function undeclaredSlotProvider(): array
    {
        return [
            // the table of a one-column table key named `v`, which TableAdapter stores like this
            'a table whose one column is named v' => [[['v' => '80'], ['v' => '81']]],
            'a table whose rows carry more columns' => [[['v' => '80', 'unit' => 'cm']]],
            'a one-entry-shaped table' => [[['v' => 'cell']]],
            'a multi select value' => [['red', 'green']],
            'the empty value of the key\'s own type' => [[]],
            'a single value' => ['80'],
            'the string zero' => ['0'],
            'false' => [false],
        ];
    }

    /**
     * The empty entry list is a state a declaring key's slot can be in, and the cell it shows is
     * the empty one. The alternative, reading `[]` as one value holding `[]`, is a value the grid
     * cell would render and the export of the same column would not
     * ({@see \Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\ClassificationStoreResolver}).
     *
     * `[]` says which of the two it is because the declaration says which shape the slot is in: the
     * sibling assertion, that `[]` at the slot of a key declaring nothing is that key's own empty
     * value, is in {@see self::undeclaredSlotProvider()}.
     */
    public function testTheEmptyEntryListOfADeclaringKeyIsTheEmptyCell(): void
    {
        $keyConfig = $this->createKeyConfig();

        $this->assertTrue($this->serializer->holdsEntries([], $keyConfig));
        $this->assertSame([], $this->describe($this->serializer->readEntries([], $keyConfig)));
        $this->assertSame([], $this->describe($this->serializer->getVisibleEntries([], $keyConfig)));
        $this->assertSame([], $this->describe($this->serializer->getHiddenEntries([], $keyConfig)));
        $this->assertNull($this->serializer->getDefaultSlotValue([], $keyConfig));
    }

    /**
     * The one reading the declaration cannot settle, settled by the members an entry may carry: at
     * a *declaring* key the slot holds either the entry list or the single value it held before the
     * key declared, and a stored value whose own type normalizes to a list of objects is that
     * second one. An entry carries `v`, `var` and `p` and nothing else, so a row with a second
     * column, a quantity value and a relation descriptor are all read whole.
     *
     * What is left of the ambiguity after this is stated where it cannot be removed: a declaring
     * key whose own type normalizes to a list of maps carrying nothing but `v`, holding a value
     * written before it declared. {@see self::testWhatAWriteStoresIsReadBackAsTheEntriesItStored()}
     * holds the other side - that such a table is still carried whole *inside* an entry.
     *
     * @dataProvider valueOfTheKeysOwnTypeProvider
     */
    public function testAStoredValueOfTheKeysOwnTypeIsNotReadAsEntries(mixed $stored): void
    {
        $keyConfig = $this->createKeyConfig();

        $this->assertFalse($this->serializer->holdsEntries($stored, $keyConfig));
        $this->assertSame($stored, $this->serializer->getDefaultSlotValue($stored, $keyConfig));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function valueOfTheKeysOwnTypeProvider(): array
    {
        return [
            'a table row carrying a second column' => [[['v' => '80', 'unit' => 'cm']]],
            'a quantity value' => [['value' => 30, 'unit' => 'cm']],
            'a relation descriptor list' => [[['type' => 'object', 'id' => 5521]]],
            'a structured value carrying v beside something else' => [[['v' => '80', 'label' => 'eighty']]],
            'a map rather than a list' => [['metric' => ['v' => '80']]],
        ];
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
            'the stored entry list, which carries the default slot' => [
                self::storedSlot([['v' => '80'], ['v' => '176', 'var' => self::IMPERIAL]]),
                '80',
            ],
            'the stored entry list, where the default slot is not first' => [
                self::storedSlot([['v' => '176', 'var' => self::IMPERIAL], ['v' => '80']]),
                '80',
            ],
            'the stored entry list, holding no default slot at all' => [
                self::storedSlot([['v' => '176', 'var' => self::IMPERIAL]]),
                '176',
            ],
            'the stored entry list, where only a hidden entry is not the default slot' => [
                self::storedSlot([['v' => '9', 'var' => 'nautical']]),
                null,
            ],
            // a key whose every entry a write cleared holds no value, and shows none
            'the stored empty entry list' => [self::storedSlot([]), null],
            // the value this key's slot held before it declared comes back untouched, whatever it
            // is. The sibling case - every value of a key that declares nothing, `[]` and a list of
            // maps carrying `v` included - is
            // {@see self::testAValueOfAKeyDeclaringNothingIsNeverReadAsEntries()}, which is where it
            // belongs: under this declaration such a list is the entry list
            'a bare value' => ['80', '80'],
            'a multi select value' => [['red', 'green'], ['red', 'green']],
            'a relation value' => [[['type' => 'object', 'id' => 7]], [['type' => 'object', 'id' => 7]]],
            'an empty slot' => [null, null],
            // legitimate values PHP would otherwise make disappear
            'the string zero' => ['0', '0'],
            'the integer zero' => [0, 0],
            'the empty string' => ['', ''],
            'false' => [false, false],
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
     * or the other: narrowing hides entries and drops none, whichever qualifier it narrows. This
     * mirrors core's `testNarrowingADeclarationKeepsTheValuesItHides`, which narrows the variants a
     * key declares and keeps the values the narrowed one hides.
     *
     * Narrowing a declaration away *entirely* is the one case this cannot answer, and the last
     * fixture says so rather than leaving it to be found: a key declaring no qualifier holds one
     * value, so its slot is not read as a list at all and there is no entry in it to call hidden.
     * The row is untouched either way - a read writes nothing - but the next write of that key
     * stores the one value it was given, and the list the row held is not carried over.
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
        $stored = self::storedSlot([
            ['v' => '80'],
            ['v' => '176', 'var' => self::IMPERIAL],
            ['v' => '81', 'p' => 1],
        ]);

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
            // the declaration is gone, so the slot holds one value: the whole list the row still
            // holds, read as the single default entry it is for a key that declares nothing. No
            // entry of it is visible and none is hidden, because none of it is read as an entry
            'the key declares nothing any more' => [
                ['fieldtype' => 'input'],
                [[null, 0]],
                [],
            ],
        ];
    }

    /**
     * The predicate the object data path asks and the read that follows it are asserted over one
     * set of fixtures rather than apart, so that the two cannot disagree: a slot that holds entries
     * reads as its entries, and one that does not reads whole as the single value it is.
     *
     * Each fixture carries the declaration it is read under, because that is the first thing asked
     * and the only thing asked of a key declaring nothing. The same list appears under both
     * declarations on purpose.
     *
     * @param array<string, mixed> $definition
     *
     * @dataProvider slotShapeProvider
     */
    public function testThePredicateAndTheReadAgreeOnWhatASlotHolds(
        array $definition,
        mixed $slot,
        bool $holds,
        int $entries
    ): void {
        $keyConfig = $this->createKeyConfigFrom($definition);

        $this->assertSame($holds, $this->serializer->holdsEntries($slot, $keyConfig));
        $this->assertCount($entries, $this->serializer->readEntries($slot, $keyConfig));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: mixed, 2: bool, 3: int}>
     */
    public static function slotShapeProvider(): array
    {
        $declaring = ['variants' => [self::METRIC, self::IMPERIAL], 'multiValue' => true];
        $declaringNothing = ['fieldtype' => 'table'];

        return [
            'the list this bundle writes' => [
                $declaring,
                self::storedSlot([['v' => '80'], ['v' => '176', 'var' => self::IMPERIAL]]),
                true,
                2,
            ],
            'a list of one entry' => [$declaring, self::storedSlot([['v' => '80']]), true, 1],
            // a key whose every entry a write cleared: the empty entry list, which is no entry
            'the empty entry list' => [$declaring, self::storedSlot([]), true, 0],
            'a bare value the key held before it declared' => [$declaring, '80', false, 1],
            'a multi select value' => [$declaring, ['red', 'green'], false, 1],
            'a relation value' => [$declaring, [['type' => 'object', 'id' => 7]], false, 1],
            'a list only partly entries' => [
                $declaring,
                self::storedSlot([['v' => '80'], ['var' => self::IMPERIAL]]),
                false,
                1,
            ],
            'a list of objects carrying a member no entry carries' => [
                $declaring,
                [['v' => '80', 'unit' => 'cm']],
                false,
                1,
            ],
            // the same list, at a key that declares nothing: the declaration refuses on its own, so
            // it is the value it is - which is what a table whose one column is named `v` holds
            'the list this bundle writes, at a key declaring nothing' => [
                $declaringNothing,
                [['v' => '80'], ['v' => '176']],
                false,
                1,
            ],
            'the empty array, at a key declaring nothing' => [$declaringNothing, [], false, 1],
            'an empty slot' => [$declaring, null, false, 1],
            // legitimate values PHP would otherwise make disappear
            'the string zero' => [$declaring, '0', false, 1],
            'the integer zero' => [$declaring, 0, false, 1],
            'the empty string' => [$declaring, '', false, 1],
            'false' => [$declaring, false, false, 1],
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
     * The slot as the row holds it at a key that declares qualifiers, which is the list a read
     * publishes and a write accepts: one shape, so a client saving back what it read writes the
     * slot it read. Named rather than spelled out per fixture, so that a fixture meant as a stored
     * slot says so where it is read under a declaration that is not asserted in the same line.
     *
     * @param array<int, array<string, mixed>> $entries
     *
     * @return array<int, array<string, mixed>>
     */
    private static function storedSlot(array $entries): array
    {
        return $entries;
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
