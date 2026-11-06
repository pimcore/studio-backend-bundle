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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Util\Trait;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;
use Pimcore\Model\Element\ElementInterface;

/**
 * Direct, black-box coverage of {@see LocalizedValueTrait::getLocalizedValue()}'s two-stage
 * fallback: (1) the value the field getter itself returns for the requested locale - which, in
 * production, already reflects Pimcore's own configured fallback chain
 * ({@see \Pimcore\Tool::getFallbackLanguagesFor()}) whenever {@see LocalizedValueTrait::doGetFallbackValues()}
 * is true - and (2) the trait's own additional "jump to the default language" step, gated by the
 * new {@see LocalizedValueTrait::allowDefaultLanguageFallback()} hook this bug fix adds.
 *
 * @internal
 */
final class LocalizedValueTraitTest extends Unit
{
    public function testGridDisplaySemanticsJumpToDefaultLanguageWhenAllowed(): void
    {
        $calls = [];
        $host = $this->makeHost(
            fallbackValuesEnabled: true,
            defaultLanguage: 'en',
            allowDefaultLanguageFallback: true,
        );
        $element = $this->makeElement($calls, ['de' => '', 'en' => 'English text']);

        $result = $host->resolve($this->makeColumn('de'), $element);

        self::assertSame('English text', $result);
        self::assertSame(['de', 'en'], $calls, 'the default-language getter must be tried as a last resort');
    }

    public function testExportConsistentSourceFieldNeverJumpsToDefaultLanguage(): void
    {
        $calls = [];
        $host = $this->makeHost(
            fallbackValuesEnabled: true,
            defaultLanguage: 'en',
            allowDefaultLanguageFallback: false,
        );
        $element = $this->makeElement($calls, ['de' => '', 'en' => 'English text']);

        $result = $host->resolve($this->makeColumn('de'), $element);

        self::assertSame('', $result, 'must keep the requested-locale value, never the default language');
        self::assertSame(['de'], $calls, 'the default-language getter must not be called once the jump is off');
    }

    public function testConfiguredFallbackLanguageAlreadyAppliedByTheGetterIsNeverOverridden(): void
    {
        // Simulates Pimcore's own configured fallback chain (Tool::getFallbackLanguagesFor) having
        // already resolved "de" to a configured fallback language's text before the trait ever sees
        // it - the requested-locale getter call itself returns a non-empty value.
        $calls = [];
        $host = $this->makeHost(
            fallbackValuesEnabled: true,
            defaultLanguage: 'en',
            allowDefaultLanguageFallback: true,
        );
        $element = $this->makeElement($calls, ['de' => 'French fallback text', 'en' => 'English text']);

        $result = $host->resolve($this->makeColumn('de'), $element);

        self::assertSame('French fallback text', $result);
        self::assertSame(
            ['de'],
            $calls,
            'the default language must not be consulted once the configured chain resolved a value'
        );
    }

    public function testDisabledAmbientFallbackNeverTriesTheDefaultLanguageEither(): void
    {
        $calls = [];
        $host = $this->makeHost(
            fallbackValuesEnabled: false,
            defaultLanguage: 'en',
            allowDefaultLanguageFallback: true,
        );
        $element = $this->makeElement($calls, ['de' => '', 'en' => 'English text']);

        $result = $host->resolve($this->makeColumn('de'), $element);

        self::assertSame('', $result);
        self::assertSame(['de'], $calls);
    }

    /**
     * @param array<string, string> $valuesByLocale
     */
    private function makeElement(array &$calls, array $valuesByLocale): LocalizedValueTraitTestElementInterface
    {
        return $this->makeEmpty(LocalizedValueTraitTestElementInterface::class, [
            'getDescription' => function (?string $language = null) use (&$calls, $valuesByLocale): ?string {
                $calls[] = $language;

                return $valuesByLocale[$language] ?? null;
            },
        ]);
    }

    private function makeHost(
        bool $fallbackValuesEnabled,
        ?string $defaultLanguage,
        bool $allowDefaultLanguageFallback,
    ): LocalizedValueTraitTestHost {
        return new LocalizedValueTraitTestHost($fallbackValuesEnabled, $defaultLanguage, $allowDefaultLanguageFallback);
    }

    private function makeColumn(string $locale): Column
    {
        return new Column(
            key: 'description',
            locale: $locale,
            type: 'dataobject.adapter',
            group: null,
            config: [],
        );
    }
}

/**
 * @internal
 */
interface LocalizedValueTraitTestElementInterface extends ElementInterface
{
    public function getDescription(?string $language = null): ?string;
}

/**
 * @internal
 */
final class LocalizedValueTraitTestHost
{
    use LocalizedValueTrait;

    public function __construct(
        private readonly bool $fallbackValuesEnabled,
        private readonly ?string $defaultLanguage,
        private readonly bool $allowDefaultLanguageFallback,
    ) {
    }

    public function resolve(Column $column, ElementInterface $element): mixed
    {
        return $this->getLocalizedValue($column, $element);
    }

    protected function doGetFallbackValues(): bool
    {
        return $this->fallbackValuesEnabled;
    }

    protected function getDefaultLanguage(): ?string
    {
        return $this->defaultLanguage;
    }

    protected function allowDefaultLanguageFallback(): bool
    {
        return $this->allowDefaultLanguageFallback;
    }
}
