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
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
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
        $this->hydrator = new KeyHydrator($this->keyLocalizationResolver);
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

    private function createKeyConfig(): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setId(42);
        $keyConfig->setName('weight');
        $keyConfig->setType('input');

        return $keyConfig;
    }
}
