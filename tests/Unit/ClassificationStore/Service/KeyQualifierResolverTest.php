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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolverInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function json_encode;

/**
 * The one place the declaration is read. Both members are reserved members of the key's `definition`
 * blob, the way `indexed` already is, so the key configuration endpoints write them verbatim and
 * every reader of them comes through here.
 *
 * @internal
 */
final class KeyQualifierResolverTest extends Unit
{
    private const string VARIANTS = 'variants';

    private const string MULTI_VALUE = 'multiValue';

    private KeyQualifierResolver $resolver;

    public function _before(): void
    {
        $this->resolver = new KeyQualifierResolver();
    }

    /**
     * The members are named once, so the layout paths, the key configuration endpoints and the
     * object data path spell them the same way.
     */
    public function testTheMembersAreNamedOnce(): void
    {
        $this->assertSame(self::VARIANTS, KeyQualifierResolverInterface::VARIANTS_MEMBER);
        $this->assertSame(self::MULTI_VALUE, KeyQualifierResolverInterface::MULTI_VALUE_MEMBER);
    }

    public function testAKeyDeclaringNeitherMemberDeclaresNoQualifiers(): void
    {
        $keyConfig = $this->createKeyConfig(['fieldtype' => 'input', 'indexed' => false]);

        $this->assertFalse($this->resolver->declaresQualifiers($keyConfig));
        $this->assertNull($this->resolver->getVariants($keyConfig));
        $this->assertNull($this->resolver->isMultiValue($keyConfig));
    }

    public function testAKeyWithNoDefinitionAtAllDeclaresNoQualifiers(): void
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');

        $this->assertFalse($this->resolver->declaresQualifiers($keyConfig));
    }

    /**
     * A definition that is not readable as JSON declares nothing rather than failing the read: it is
     * the same blob the key configuration endpoints hand back verbatim.
     */
    public function testADefinitionThatIsNotJsonDeclaresNoQualifiers(): void
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');
        $keyConfig->setDefinition('{not json');

        $this->assertFalse($this->resolver->declaresQualifiers($keyConfig));
        $this->assertNull($this->resolver->getVariants($keyConfig));
        $this->assertNull($this->resolver->isMultiValue($keyConfig));
    }

    public function testTheVariantsAreReadInTheOrderTheKeyDeclaresThem(): void
    {
        $keyConfig = $this->createKeyConfig([self::VARIANTS => ['metric', 'imperial']]);

        $this->assertSame(['metric', 'imperial'], $this->resolver->getVariants($keyConfig));
        $this->assertTrue($this->resolver->declaresQualifiers($keyConfig));
        $this->assertNull($this->resolver->isMultiValue($keyConfig));
    }

    /**
     * @dataProvider multiValueProvider
     */
    public function testTheMultiValueSettingIsReadAsTheKeyStoresIt(mixed $stored, ?bool $expected): void
    {
        $keyConfig = $this->createKeyConfig([self::MULTI_VALUE => $stored]);

        $this->assertSame($expected, $this->resolver->isMultiValue($keyConfig));
    }

    /**
     * @return array<string, array{0: mixed, 1: ?bool}>
     */
    public static function multiValueProvider(): array
    {
        return [
            'a repeatable key' => [true, true],
            'a key that states it holds one value' => [false, false],
            // a blob carrying the raw column - a tinyint, say - states nothing rather than true
            'a non boolean states nothing' => [1, null],
            'an explicit null states nothing' => [null, null],
        ];
    }

    /**
     * A declared name that is not a non-empty string is left out rather than published as one: the
     * default variant carries no name, so an empty name would be indistinguishable from it.
     */
    public function testANameThatIsNotANonEmptyStringIsLeftOut(): void
    {
        $keyConfig = $this->createKeyConfig([self::VARIANTS => ['metric', '', 17, null, 'imperial']]);

        $this->assertSame(['metric', 'imperial'], $this->resolver->getVariants($keyConfig));
    }

    /**
     * The declaration is read, not its mere presence. Each of these keys carries a member and still
     * permits exactly the one slot it always had, so each keeps serialising as a single value - the
     * case that decides whether every key of every store that already exists changes shape.
     *
     * @dataProvider declaresNoQualifiersProvider
     *
     * @param array<string, mixed> $definition
     */
    public function testADeclarationPermittingOnlyTheDefaultSlotDeclaresNoQualifiers(array $definition): void
    {
        $this->assertFalse($this->resolver->declaresQualifiers($this->createKeyConfig($definition)));
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function declaresNoQualifiersProvider(): array
    {
        return [
            'no variant beside the default one' => [[self::VARIANTS => []]],
            'one value per variant' => [[self::MULTI_VALUE => false]],
            // the shape a core that grew the two columns hands back for a key using neither
            'both, stating the single slot' => [[self::VARIANTS => [], self::MULTI_VALUE => false]],
            'a variants member that is not a list' => [[self::VARIANTS => 'metric']],
            'a multiValue member that is not a boolean' => [[self::MULTI_VALUE => 0]],
            // the only declared name is unusable, so the declaration names no variant at all
            'a variant list holding no usable name' => [[self::VARIANTS => ['']]],
        ];
    }

    /**
     * The members are still published as the key stores them, so a key stating the single slot
     * reports its statement rather than reporting nothing.
     */
    public function testTheSingleSlotStatementIsStillPublished(): void
    {
        $keyConfig = $this->createKeyConfig([self::VARIANTS => [], self::MULTI_VALUE => false]);

        $this->assertSame([], $this->resolver->getVariants($keyConfig));
        $this->assertFalse($this->resolver->isMultiValue($keyConfig));
    }

    /**
     * @dataProvider declaresQualifiersProvider
     *
     * @param array<string, mixed> $definition
     */
    public function testADeclarationPermittingMoreThanTheDefaultSlotDeclaresQualifiers(array $definition): void
    {
        $this->assertTrue($this->resolver->declaresQualifiers($this->createKeyConfig($definition)));
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function declaresQualifiersProvider(): array
    {
        return [
            'a variant name' => [[self::VARIANTS => ['metric']]],
            'repeatable' => [[self::MULTI_VALUE => true]],
            'both' => [[self::VARIANTS => ['metric', 'imperial'], self::MULTI_VALUE => true]],
            'repeatable with no variant beside the default one' => [[
                self::VARIANTS => [],
                self::MULTI_VALUE => true,
            ]],
        ];
    }

    /**
     * @dataProvider notAVariantListProvider
     */
    public function testAVariantsMemberThatIsNotAListStatesNothing(mixed $stored): void
    {
        $keyConfig = $this->createKeyConfig([self::VARIANTS => $stored]);

        $this->assertNull($this->resolver->getVariants($keyConfig));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function notAVariantListProvider(): array
    {
        return [
            'a string' => ['metric'],
            'a boolean' => [true],
            'an explicit null' => [null],
        ];
    }

    public function testDeclaringMultiValueAloneIsADeclaration(): void
    {
        $keyConfig = $this->createKeyConfig([self::MULTI_VALUE => true]);

        $this->assertTrue($this->resolver->declaresQualifiers($keyConfig));
        $this->assertNull($this->resolver->getVariants($keyConfig));
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function createKeyConfig(array $definition): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');
        $keyConfig->setDefinition((string) json_encode($definition));

        return $keyConfig;
    }
}
