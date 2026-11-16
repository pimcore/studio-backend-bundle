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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\VariantLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ProvidedOptionNarrower;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use ReflectionClass;
use function array_column;
use function array_map;
use function json_encode;
use function str_contains;
use function strtoupper;
use Psr\Log\NullLogger;

/**
 * The label is resolved through core's resolver, so none of the chain below the title column is
 * asserted here - only the rung this endpoint ends on, which is null rather than the technical name.
 * The per-key KeyConfig load stays; what the group endpoint no longer issues is a label query per key.
 *
 * @internal
 */
final class KeyGroupLayoutServiceTest extends Unit
{
    use LabelResolverMockTrait;

    private const int KEY_ID = 7;

    private const int GROUP_ID = 3;

    private const string KEY_NAME = 'weight';

    private const string LANGUAGE = 'de';

    private const string DEFAULT_LANGUAGE = 'en';

    private const string LABEL = 'Gewicht';

    private const string COLUMN_TITLE = 'classificationstore.key.weight';

    private const string SUB_GROUP = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    private const string FIELD_NAME = 'classificationStore';

    private const string SELECT = 'select';

    private const string MULTISELECT = 'multiselect';

    private const string OPTIONS = 'options';

    private const string FROM_JSON = 'getFieldDefinitionFromJson';

    /**
     * The vocabulary a key holds, or a provider hands over, in the order it is handed over.
     */
    private const array OPTION_VALUES = ['a', 'b', 'c', 'd', 'e'];

    /**
     * What every assignment below narrows to.
     */
    private const array SUBSET_VALUES = ['b', self::KEPT_VALUE];

    /**
     * One value the subset keeps, and two it leaves out.
     */
    private const string KEPT_VALUE = 'd';

    private const string EXCLUDED_VALUE = 'e';

    private const string OTHER_EXCLUDED_VALUE = 'a';

    public function _before(): void
    {
        ConfigurableOptionsProvider::reset();
    }

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsTheLabelOfTheRequestingUsersLanguage(): void
    {
        $keyLayout = $this->getKeyLayout(
            $this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE),
            [self::KEY_ID => [self::LANGUAGE => self::LABEL]]
        );

        $this->assertSame(self::LABEL, $keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());

        foreach ((new ReflectionClass(KeyGroupLayoutService::class))->getConstructor()->getParameters() as $parameter) {
            $this->assertFalse(str_contains((string)$parameter->getType(), 'Translator'));
        }
    }

    /**
     * An installation with an empty labels table is unchanged from the release before: the chain ends
     * at the key's title column.
     *
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsTheTitleColumnWithoutALabelRow(): void
    {
        $keyLayout = $this->getKeyLayout($this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE));

        $this->assertSame(self::COLUMN_TITLE, $keyLayout->getTitle());
    }

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsNullWhenNoTitleIsStored(): void
    {
        $keyLayout = $this->getKeyLayout($this->createKeyConfig(self::KEY_ID, self::KEY_NAME));

        $this->assertNull($keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());
    }

    /**
     * classificationstore_keys.title is NOT NULL DEFAULT '', so an empty title is the ordinary case
     * rather than an exotic one. It reaches the response as null, not as the empty string it was.
     *
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsNullForAnEmptyTitleColumn(): void
    {
        $keyLayout = $this->getKeyLayout($this->createKeyConfig(self::KEY_ID, self::KEY_NAME, ''));

        $this->assertNull($keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsNullWhenTheKeyConfigurationCannotBeLoaded(): void
    {
        $keyLayout = $this->getKeyLayout(null);

        $this->assertNull($keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());
    }

    /**
     * A user whose language is unset is labelled in the installation default language rather than
     * against an empty one.
     *
     * @throws Exception
     */
    public function testAnEmptyUserLanguageFallsBackToTheDefaultLanguage(): void
    {
        $keyLayout = $this->getKeyLayout(
            $this->createKeyConfig(self::KEY_ID, self::KEY_NAME),
            [self::KEY_ID => [self::DEFAULT_LANGUAGE => self::LABEL]],
            ''
        );

        $this->assertSame(self::LABEL, $keyLayout->getTitle());
        $this->assertSame([self::DEFAULT_LANGUAGE], array_column($this->labelResolverCalls, 'language'));
    }

    /**
     * This endpoint builds its own KeyLayout rather than going through the group layout hydrator, so the
     * sub-group identifier reaches the response along a second, independent path. It is the raw identifier
     * of the assignment: this payload has no sub-group vocabulary to resolve it against, and nothing here
     * may substitute a name for it.
     *
     * @throws Exception
     */
    public function testGetKeyLayoutCarriesTheRawSubGroupIdentifierOfTheRelation(): void
    {
        $keyLayout = $this->getKeyLayout(
            $this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE),
            subGroup: self::SUB_GROUP
        );

        $this->assertSame(self::SUB_GROUP, $keyLayout->getSubGroup());
    }

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsNoSubGroupWhenTheRelationCarriesNone(): void
    {
        $keyLayout = $this->getKeyLayout($this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE));

        $this->assertNull($keyLayout->getSubGroup());
    }

    /**
     * The key's own `localized` flag reaches this response too, passed through as the tri-state it
     * is: the editor gets a key's localization with the layout it already renders and needs no
     * second request for it.
     *
     * @dataProvider localizedProvider
     *
     * @throws Exception
     */
    public function testGetKeyLayoutCarriesTheLocalizationFlagOfTheKey(?bool $localized): void
    {
        $keyLayout = $this->getKeyLayout(
            $this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE),
            localized: $localized
        );

        $this->assertSame($localized, $keyLayout->getLocalized());
    }

    /**
     * @return array<string, array{0: ?bool}>
     */
    public static function localizedProvider(): array
    {
        return [
            'a key flagged localized' => [true],
            'a key flagged not localized' => [false],
            'a key stating nothing' => [null],
        ];
    }

    /**
     * A key whose configuration cannot be loaded reports nothing rather than a decision, the way
     * its title does.
     *
     * @throws Exception
     */
    public function testGetKeyLayoutReportsNoLocalizationFlagWithoutAKeyConfiguration(): void
    {
        $this->assertNull($this->getKeyLayout(null)->getLocalized());
    }

    /**
     * The layout-by-key path publishes what the key declares about its qualified values, off the
     * same key configuration its title and its `localized` flag come from. The group layout path
     * has the same assertion in
     * {@see GroupServiceTest::testEveryKeyCarriesWhatItDeclaresAboutItsQualifiedValues()}.
     *
     * @throws Exception
     */
    public function testTheKeyLayoutCarriesWhatTheKeyDeclaresAboutItsValues(): void
    {
        $keyConfig = $this->createKeyConfig(self::KEY_ID, self::KEY_NAME);
        $keyConfig->setType('multiValue');
        $keyConfig->setDefinition(
            '{"variants":[{"name":"metric","label":"Metric"},"imperial"],"multiValue":true,"maximum":3,'
            . '"unqualifiedValue":false,"unqualifiedValueLabel":"Standard"}'
        );

        $keyLayout = $this->getKeyLayout($keyConfig);
        $this->assertFalse($keyLayout->getDefaultVariant());
        $this->assertSame('Standard', $keyLayout->getDefaultVariantLabel());

        $this->assertEquals(
            [new VariantLayout('metric', 'Metric'), new VariantLayout('imperial')],
            $keyLayout->getVariants()
        );
        $this->assertTrue($keyLayout->getMultiValue());
        $this->assertSame(3, $keyLayout->getMaximum());
    }

    public function testTheLayoutDefinitionIsBuiltFromTheOptionsTheAssignmentNarrowedTo(): void
    {
        $relation = $this->relation($this->blob([self::OPTIONS => $this->options()]));

        $received = null;
        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->method(self::FROM_JSON)
            ->willReturnCallback(static function (array $definition) use (&$received): Input {
                $received = $definition;

                return new Input();
            });

        $this->createLayoutService($serviceResolver)->getLayoutDefinition($relation, self::FIELD_NAME);

        $this->assertSame(self::SUBSET_VALUES, $this->valuesOf($received[self::OPTIONS]));
    }

    /**
     * The regression this covers: the shared helper strips the subset off a definition whose list a
     * provider fills, because the provider replaces the list wholesale a moment later. Core
     * re-applies the subset to what the provider handed over
     * ({@see \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore::enrichLayoutDefinition()});
     * this path did not, and offered the editor every option the configuration excludes.
     */
    public function testTheLayoutDefinitionNarrowsTheListTheProviderFilledIn(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();

        $definition = $this->enrichedLayoutDefinition($this->providerBackedSelect());

        $this->assertInstanceOf(Select::class, $definition);
        $this->assertSame(self::SUBSET_VALUES, $this->valuesOf($definition->getOptions()));
    }

    /**
     * The provider's default is reconciled against the narrowed list, so a group never offers a
     * default its own subset excludes.
     */
    public function testADefaultTheSubsetExcludesIsDropped(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();
        ConfigurableOptionsProvider::$defaultValue = self::EXCLUDED_VALUE;

        $definition = $this->enrichedLayoutDefinition($this->providerBackedSelect());

        $this->assertInstanceOf(Select::class, $definition);
        $this->assertNull($definition->getDefaultValue());
    }

    public function testADefaultTheSubsetKeepsSurvives(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();
        ConfigurableOptionsProvider::$defaultValue = self::KEPT_VALUE;

        $definition = $this->enrichedLayoutDefinition($this->providerBackedSelect());

        $this->assertInstanceOf(Select::class, $definition);
        $this->assertSame(self::KEPT_VALUE, $definition->getDefaultValue());
    }

    /**
     * The `multiselect` half of the same block: {@see ProvidedOptionNarrower} narrows both types, as
     * core does, so an assignment's subset reaches a provider-backed multiselect as it does a select.
     */
    public function testTheLayoutDefinitionNarrowsTheListTheProviderFilledInForAMultiselect(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();

        $this->assertSame(
            self::SUBSET_VALUES,
            $this->valuesOf($this->enrichedMultiselectDefinition()->getOptions())
        );
    }

    /**
     * A multiselect declares its default as a list, so reconciliation drops the entries the subset
     * excludes and keeps the rest - it does not discard the whole default over one excluded entry.
     */
    public function testAMultiselectDefaultKeepsOnlyTheValuesTheSubsetAllows(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();
        ConfigurableOptionsProvider::$defaultValue = [self::KEPT_VALUE, self::EXCLUDED_VALUE];

        $this->assertSame([self::KEPT_VALUE], $this->enrichedMultiselectDefinition()->getDefaultValue());
    }

    /**
     * A list the subset keeps whole is handed on unchanged, in the order it was declared in.
     */
    public function testAMultiselectDefaultTheSubsetKeepsEntirelySurvives(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();
        ConfigurableOptionsProvider::$defaultValue = self::SUBSET_VALUES;

        $this->assertSame(self::SUBSET_VALUES, $this->enrichedMultiselectDefinition()->getDefaultValue());
    }

    /**
     * Nothing surviving is reported as no default at all rather than as an empty list, which is what
     * core's reconciliation hands back and what an editor reads as "unset".
     */
    public function testAMultiselectDefaultTheSubsetExcludesEntirelyIsDropped(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();
        ConfigurableOptionsProvider::$defaultValue = [self::OTHER_EXCLUDED_VALUE, self::EXCLUDED_VALUE];

        $this->assertNull($this->enrichedMultiselectDefinition()->getDefaultValue());
    }

    /**
     * A provider reporting that its list is not static is re-resolved per edit and carries no
     * subset, so core leaves it whole. Narrowing it here would differ from the group the editor
     * renders through core.
     */
    public function testAListTheProviderReportsAsDynamicIsLeftWhole(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();
        ConfigurableOptionsProvider::$staticOptions = false;

        $definition = $this->enrichedLayoutDefinition($this->providerBackedSelect());

        $this->assertInstanceOf(Select::class, $definition);
        $this->assertSame(self::OPTION_VALUES, $this->valuesOf($definition->getOptions()));
    }

    /**
     * The regression this covers: the grid's field-filter panel asks for a layout with no object in
     * context, and neither the enrichment nor the subset was reached, so the dropdown was handed an
     * empty list and offered "Empty" alone. The stored blob is no fallback - the shared helper
     * strips the option member off a definition whose list a provider fills.
     */
    public function testTheLayoutDefinitionNarrowsTheProvidersListWithNoObjectInContext(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();

        $this->assertSame(
            self::SUBSET_VALUES,
            $this->valuesOf($this->enrichedSelectDefinition(withObject: false)->getOptions())
        );
    }

    /**
     * The two paths answer with the same list, which is the whole point of enriching without an
     * object: one endpoint may not offer the editor one vocabulary and the filter panel another.
     */
    public function testTheObjectLessPathAnswersTheSameListAsTheObjectPath(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();
        $withObject = $this->valuesOf($this->enrichedSelectDefinition()->getOptions());

        ConfigurableOptionsProvider::$options = $this->options();
        $withoutObject = $this->valuesOf($this->enrichedSelectDefinition(withObject: false)->getOptions());

        $this->assertSame($withObject, $withoutObject);
        $this->assertSame(self::SUBSET_VALUES, $withoutObject);
    }

    /**
     * The provider's default is reconciled against the narrowed list without an object too.
     */
    public function testADefaultTheSubsetExcludesIsDroppedWithoutAnObject(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();
        ConfigurableOptionsProvider::$defaultValue = self::EXCLUDED_VALUE;

        $this->assertNull($this->enrichedSelectDefinition(withObject: false)->getDefaultValue());
    }

    /**
     * The `multiselect` half of the object-less path, as {@see ProvidedOptionNarrower} narrows both.
     */
    public function testTheLayoutDefinitionNarrowsAMultiselectWithNoObjectInContext(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();

        $this->assertSame(
            self::SUBSET_VALUES,
            $this->valuesOf($this->enrichedMultiselectDefinition(withObject: false)->getOptions())
        );
    }

    /**
     * A provider reporting that its list is not static is re-resolved per edit and carries no subset,
     * so core leaves it whole - object or no object. Narrowing it here would make this endpoint
     * disagree with the group the editor renders through core, which is what this fix is about.
     */
    public function testAListTheProviderReportsAsDynamicIsLeftWholeWithoutAnObject(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();
        ConfigurableOptionsProvider::$staticOptions = false;

        $this->assertSame(
            self::OPTION_VALUES,
            $this->valuesOf($this->enrichedSelectDefinition(withObject: false)->getOptions())
        );
    }

    /**
     * The dynamic list is the same whichever path asked for it.
     */
    public function testADynamicListIsLeftWholeOnBothPaths(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();
        ConfigurableOptionsProvider::$staticOptions = false;

        $this->assertSame(
            $this->valuesOf($this->enrichedSelectDefinition()->getOptions()),
            $this->valuesOf($this->enrichedSelectDefinition(withObject: false)->getOptions())
        );
    }

    /**
     * A provider that cannot answer leaves the layout as it stands rather than failing the request,
     * the tolerance {@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyOptionsResolver}
     * already publishes an unresolved key with.
     */
    public function testAProviderThatRaisesLeavesTheLayoutStandingWithoutAnObject(): void
    {
        $definition = $this->enrichedLayoutDefinition(
            $this->providerBackedSelect(RaisingOptionsProvider::class),
            withObject: false
        );

        $this->assertInstanceOf(Select::class, $definition);
        $this->assertSame([], $this->valuesOf($definition->getOptions()));
    }

    /**
     * A key that names no provider at all is handed over as it was stored, and asks nothing.
     */
    public function testAKeyWithoutAProviderIsHandedOverUnchangedWithoutAnObject(): void
    {
        $select = new Select();
        $select->setName(self::KEY_NAME);
        $select->setOptions($this->options(self::SUBSET_VALUES));

        $definition = $this->enrichedLayoutDefinition($select, withObject: false);

        $this->assertInstanceOf(Select::class, $definition);
        $this->assertSame(self::SUBSET_VALUES, $this->valuesOf($definition->getOptions()));
    }

    /**
     * An `encryptedField` forwards the enrichment to the type it wraps, which is where the provider
     * ran and where the subset belongs.
     */
    public function testTheSubsetReachesTheTypeAWrapperDelegatesTo(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();

        $delegate = $this->providerBackedSelect();
        $wrapper = new EncryptedField();
        $wrapper->setDelegate($delegate);

        $this->enrichedLayoutDefinition($wrapper);

        $this->assertSame(self::SUBSET_VALUES, $this->valuesOf($delegate->getOptions()));
    }

    /**
     * The wrapper delegates without an object too: core's object-less entry point is implemented by
     * the type that answers for the value, not by the `encryptedField` around it.
     */
    public function testTheSubsetReachesTheDelegateOfAWrapperWithoutAnObject(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();

        $delegate = $this->providerBackedSelect();
        $wrapper = new EncryptedField();
        $wrapper->setDelegate($delegate);

        $this->enrichedLayoutDefinition($wrapper, withObject: false);

        $this->assertSame(self::SUBSET_VALUES, $this->valuesOf($delegate->getOptions()));
    }

    /**
     * An assignment that narrows nothing leaves the provider's list exactly as it was handed over.
     */
    public function testAnAssignmentWithoutASubsetLeavesTheProvidersListAlone(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();

        $definition = $this->enrichedLayoutDefinition($this->providerBackedSelect(), null);

        $this->assertInstanceOf(Select::class, $definition);
        $this->assertSame(self::OPTION_VALUES, $this->valuesOf($definition->getOptions()));
    }

    /**
     * An assignment that narrows nothing still has the provider asked for its list without an
     * object: the enrichment is what fills the list, and the subset is a second, separate step.
     */
    public function testAnAssignmentWithoutASubsetStillGetsTheProvidersListWithoutAnObject(): void
    {
        ConfigurableOptionsProvider::$options = $this->options();

        $definition = $this->enrichedLayoutDefinition($this->providerBackedSelect(), null, withObject: false);

        $this->assertInstanceOf(Select::class, $definition);
        $this->assertSame(self::OPTION_VALUES, $this->valuesOf($definition->getOptions()));
    }

    /**
     * @throws Exception
     */
    public function testAKeyOfAnotherTypeReportsNullEvenWhereItsBlobCarriesTheMembers(): void
    {
        $keyConfig = $this->createKeyConfig(self::KEY_ID, self::KEY_NAME);
        $keyConfig->setType('quantityValue');
        $keyConfig->setDefinition('{"variants":["metric"],"multiValue":true,"maximum":3}');

        $keyLayout = $this->getKeyLayout($keyConfig);

        $this->assertNull($keyLayout->getVariants());
        $this->assertNull($keyLayout->getMultiValue());
        $this->assertNull($keyLayout->getMaximum());
        $this->assertNull($keyLayout->getDefaultVariant());
        $this->assertNull($keyLayout->getDefaultVariantLabel());
    }

    /**
     * A key declaring neither member reports null for both, and so does a key whose configuration
     * cannot be loaded at all - the way its title and its `localized` flag do.
     *
     * @throws Exception
     */
    public function testAKeyDeclaringNothingReportsNullOnBothMembers(): void
    {
        $declaringNothing = $this->getKeyLayout($this->createKeyConfig(self::KEY_ID, self::KEY_NAME));

        $this->assertNull($declaringNothing->getVariants());
        $this->assertNull($declaringNothing->getMultiValue());
        $this->assertNull($declaringNothing->getMaximum());

        $this->assertNull($this->getKeyLayout(null)->getVariants());
        $this->assertNull($this->getKeyLayout(null)->getMultiValue());
        $this->assertNull($this->getKeyLayout(null)->getMaximum());
    }

    /**
     * @throws Exception
     */
    public function testPreloadKeyTitlesReadsAWholeGroupWithOneCall(): void
    {
        $keyIds = [11, 12, 13];

        $this->createService(null)->preloadKeyTitles($keyIds, self::LANGUAGE);

        $this->assertSame([$keyIds], $this->preloadedIds());
    }

    /**
     * @param array<int, array<string, string>> $labels
     *
     * @throws Exception
     */
    private function getKeyLayout(
        ?KeyConfig $keyConfig,
        array $labels = [],
        ?string $userLanguage = self::LANGUAGE,
        ?string $subGroup = null,
        ?bool $localized = null
    ): KeyLayout {
        return $this
            ->createService($keyConfig, $labels, $userLanguage, $subGroup, $localized)
            ->getKeyLayout(new LayoutParameter(self::FIELD_NAME), self::KEY_ID, self::GROUP_ID);
    }

    /**
     * The options of {@see self::OPTION_VALUES}, or of the values named, each labelled by its own
     * value in upper case.
     *
     * @param string[] $values
     *
     * @return array<int, array<string, string>>
     */
    private function options(array $values = self::OPTION_VALUES): array
    {
        return array_map(
            static fn (string $value): array => ['key' => strtoupper($value), 'value' => $value],
            $values
        );
    }

    /**
     * @param array<int, array<string, mixed>>|null $options
     *
     * @return string[]
     */
    private function valuesOf(?array $options): array
    {
        return array_column($options ?? [], 'value');
    }

    /**
     * @param array<string, mixed> $members
     */
    private function blob(array $members, string $type = self::SELECT): string
    {
        return (string)json_encode(['fieldtype' => $type] + $members);
    }

    /**
     * An assignment of the key, of the type named and narrowing the list to {@see self::SUBSET_VALUES}
     * unless told otherwise.
     *
     * @param string[]|null $subset
     */
    private function relation(
        string $definition,
        ?array $subset = self::SUBSET_VALUES,
        string $type = self::SELECT
    ): KeyGroupRelation {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setType($type);
        $relation->setDefinition($definition);
        $relation->setDefinitionOverride(
            $subset === null ? null : [self::OPTIONS => $this->options($subset)]
        );

        return $relation;
    }

    /**
     * A select that holds no list of its own and leaves it to {@see ConfigurableOptionsProvider}.
     */
    private function providerBackedSelect(string $providerClass = ConfigurableOptionsProvider::class): Select
    {
        $select = new Select();
        $select->setName(self::KEY_NAME);
        $select->setOptionsProviderClass($providerClass);

        return $select;
    }

    /**
     * The multiselect counterpart of {@see self::providerBackedSelect()}.
     */
    private function providerBackedMultiselect(): Multiselect
    {
        $multiselect = new Multiselect();
        $multiselect->setName(self::KEY_NAME);
        $multiselect->setOptionsProviderClass(ConfigurableOptionsProvider::class);

        return $multiselect;
    }

    /**
     * What a provider-backed `multiselect` assignment hands the editor, narrowed and typed.
     */
    private function enrichedMultiselectDefinition(bool $withObject = true): Multiselect
    {
        $definition = $this->enrichedLayoutDefinition(
            $this->providerBackedMultiselect(),
            type: self::MULTISELECT,
            withObject: $withObject
        );

        $this->assertInstanceOf(Multiselect::class, $definition);

        return $definition;
    }

    /**
     * The `select` counterpart of {@see self::enrichedMultiselectDefinition()}.
     */
    private function enrichedSelectDefinition(bool $withObject = true): Select
    {
        $definition = $this->enrichedLayoutDefinition($this->providerBackedSelect(), withObject: $withObject);

        $this->assertInstanceOf(Select::class, $definition);

        return $definition;
    }

    /**
     * The definition the layout path hands over for a provider-backed key, enrichment included -
     * with an object in context or, as the grid's field-filter panel asks for it, without one.
     *
     * @param string[]|null $subset
     */
    private function enrichedLayoutDefinition(
        Data $definition,
        ?array $subset = self::SUBSET_VALUES,
        string $type = self::SELECT,
        bool $withObject = true
    ): Data {
        $relation = $this->relation(
            $this->blob(['optionsProviderClass' => ConfigurableOptionsProvider::class], $type),
            $subset,
            $type
        );

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method(self::FROM_JSON)->willReturn($definition);

        $object = null;

        if ($withObject) {
            $object = $this->createMock(Concrete::class);
            $object->method('getClass')->willReturn(new ClassDefinition());
        }

        return $this->createLayoutService($serviceResolver)->getLayoutDefinition(
            $relation,
            self::FIELD_NAME,
            $object
        );
    }

    private function createLayoutService(ServiceResolverInterface $serviceResolver): KeyGroupLayoutService
    {
        return new KeyGroupLayoutService(
            $serviceResolver,
            $this->createMock(KeyRelationResolverInterface::class),
            new ConcreteObjectResolver(),
            $this->createMock(KeyConfigResolverInterface::class),
            $this->createLabelResolver([]),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE),
            new InMemoryKeyLocalizationResolver(),
            new KeyQualifierResolver($serviceResolver),
            new ProvidedOptionNarrower(),
            new NullLogger()
        );
    }

    /**
     * @param array<int, array<string, string>> $labels
     *
     * @throws Exception
     */
    private function createService(
        ?KeyConfig $keyConfig,
        array $labels = [],
        ?string $userLanguage = self::LANGUAGE,
        ?string $subGroup = null,
        ?bool $localized = null
    ): KeyGroupLayoutService {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName(self::KEY_NAME);
        $relation->setDescription('The weight of the product');
        $relation->setDefinition('{"fieldtype":"input"}');
        $relation->setType('input');
        $relation->setSubGroup($subGroup);

        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroupAndKey')
            ->with(self::GROUP_ID, self::KEY_ID)
            ->willReturn($relation);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->method(self::FROM_JSON)
            ->willReturnCallback(static function (array $definition, string $type): Input|MultiValue {
                if ($type !== 'multiValue') {
                    return new Input();
                }

                $multiValue = DeclaredMultiValue::from($definition);
                $multiValue->setVariants($definition['variants'] ?? []);
                $multiValue->setMultiValue($definition['multiValue'] ?? false);
                $multiValue->setMaximum($definition['maximum'] ?? null);

                return $multiValue;
            });

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->with(self::KEY_ID)
            ->willReturn($keyConfig);

        $keyLocalizationResolver = new InMemoryKeyLocalizationResolver();

        if ($keyConfig !== null && $localized !== null) {
            $keyLocalizationResolver->setLocalized($keyConfig, $localized);
        }

        return new KeyGroupLayoutService(
            $serviceResolver,
            $keyRelationResolver,
            new ConcreteObjectResolver(),
            $keyConfigResolver,
            $this->createLabelResolver($labels),
            $this->createLabelLanguageResolver($userLanguage, self::DEFAULT_LANGUAGE),
            $keyLocalizationResolver,
            new KeyQualifierResolver($serviceResolver),
            new ProvidedOptionNarrower(),
            new NullLogger()
        );
    }
}
