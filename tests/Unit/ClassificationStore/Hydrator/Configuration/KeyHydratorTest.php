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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Hydrator\Configuration;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUnits;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyOptionsResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\OptionsUnavailableReason;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\NullKeyOptionsResolver;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * @internal
 */
final class KeyHydratorTest extends Unit
{
    private KeyHydrator $hydrator;

    private InMemoryKeyLocalizationResolver $keyLocalizationResolver;

    public function _before(): void
    {
        $this->keyLocalizationResolver = new InMemoryKeyLocalizationResolver();
        $this->hydrator = new KeyHydrator($this->keyLocalizationResolver, new NullKeyOptionsResolver());
    }

    public function testStoredTitleIsPassedThroughVerbatim(): void
    {
        $keyConfig = $this->createKeyConfig();
        $keyConfig->setTitle('Weight');

        $this->assertSame('Weight', $this->hydrator->hydrateKeyDetail($keyConfig)->getTitle());
    }

    public function testUnsetTitleIsNotSubstitutedWithTheName(): void
    {
        $keyConfig = $this->createKeyConfig();

        $this->assertSame(
            $keyConfig->getTitle(),
            $this->hydrator->hydrateKeyDetail($keyConfig)->getTitle()
        );
    }

    /**
     * The definition is reported as it is stored, so the search index flag a created assignment of
     * this key defaults from is readable without a property of its own on the response.
     */
    public function testADefinitionCarryingTheSearchIndexFlagIsReportedVerbatim(): void
    {
        $keyConfig = $this->createKeyConfig();
        $keyConfig->setDefinition('{"fieldtype":"input","indexed":false}');

        $this->assertSame(
            ['fieldtype' => 'input', 'indexed' => false],
            $this->hydrator->hydrateKeyDetail($keyConfig)->getDefinition()
        );
    }

    /**
     * The detail endpoint publishes the definition blob opaquely: `variants` and `multiValue` survive
     * the decode the read performs, in the order and with the types they were stored with, whatever the
     * key's type is - whether they declare anything is the key type's answer, not this read's.
     */
    public function testTheQualifiedValueDeclarationSurvivesTheDecodeVerbatim(): void
    {
        $keyConfig = $this->createKeyConfig();
        $keyConfig->setDefinition(
            '{"fieldtype":"quantityValue","variants":["metric","imperial"],"multiValue":true}'
        );

        $this->assertSame(
            [
                'fieldtype' => 'quantityValue',
                'variants' => ['metric', 'imperial'],
                'multiValue' => true,
            ],
            $this->hydrator->hydrateKeyDetail($keyConfig)->getDefinition()
        );
    }

    /**
     * The flag is reported exactly as the key stores it and resolved against nothing: the key
     * states true, states false, or states nothing at all, and the third is null rather than either
     * of the first two. The class field's own flag decides only for that third case, and only in
     * the client - nothing here looks at it.
     *
     * @dataProvider localizedProvider
     */
    public function testTheLocalizationFlagIsReportedAsTheKeyStoresIt(?bool $stored): void
    {
        $keyConfig = $this->createKeyConfig();

        // the third case is left unwritten, which is the key that states nothing
        if ($stored !== null) {
            $this->keyLocalizationResolver->setLocalized($keyConfig, $stored);
        }

        $this->assertSame($stored, $this->hydrator->hydrateKeyDetail($keyConfig)->getLocalized());
    }

    /**
     * @return array<string, array{0: ?bool}>
     */
    public static function localizedProvider(): array
    {
        return [
            'a key flagged localized reports true' => [true],
            'a key flagged not localized reports false' => [false],
            'a key stating nothing reports null' => [null],
        ];
    }

    /**
     * The resolved members come from the resolver and the stored definition is reported exactly as
     * stored beside them, never replaced by the resolved options.
     */
    public function testTheResolvedMembersArePublishedBesideTheUntouchedDefinition(): void
    {
        $options = [new SelectOption('A', 'a')];
        $units = new KeyUnits('kg', ['kg', 'g']);
        $resolver = $this->createMock(KeyOptionsResolverInterface::class);
        $resolver->method('getOptions')->willReturn($options);
        $resolver->method('getOptionsUnavailableReason')->willReturn(OptionsUnavailableReason::PROVIDER_NOT_STATIC);
        $resolver->method('getUnits')->willReturn($units);

        $keyConfig = $this->createKeyConfig();
        $keyConfig->setDefinition('{"fieldtype":"select","optionsProviderClass":"StaticProvider"}');

        $detail = (new KeyHydrator($this->keyLocalizationResolver, $resolver))->hydrateKeyDetail($keyConfig);

        $this->assertSame($options, $detail->getOptions());
        $this->assertSame('providerNotStatic', $detail->getOptionsUnavailableReason());
        $this->assertSame($units, $detail->getUnits());
        $this->assertSame(
            ['fieldtype' => 'select', 'optionsProviderClass' => 'StaticProvider'],
            $detail->getDefinition()
        );
        $this->assertSame('input', $detail->getType());
    }

    public function testAKeyThatResolvesNothingPublishesNullMembers(): void
    {
        $detail = $this->hydrator->hydrateKeyDetail($this->createKeyConfig());

        $this->assertNull($detail->getOptions());
        $this->assertNull($detail->getOptionsUnavailableReason());
        $this->assertNull($detail->getUnits());
    }

    private function createKeyConfig(): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setId(42);
        $keyConfig->setName('weight');
        $keyConfig->setType('input');

        return $keyConfig;
    }
}
