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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolverInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function method_exists;

/**
 * The production bridge, not a substitute for it: the other tests of this feature stand
 * {@see InMemoryKeyLocalizationResolver} in for it, and would pass if this getter always answered
 * null or this setter forwarded nothing.
 *
 * `composer.json` allows a core whose `classificationstore_keys` has no `localized` column, and such
 * a core declares neither accessor, so the bridge has two halves and each needs a model of its own
 * to be read against: the capable half against {@see LocalizationCapableKeyConfig}, through the entry
 * points the bridge exposes for it, and the fallback half against a model with neither accessor and
 * against the real `KeyConfig`.
 *
 * @internal
 */
final class KeyLocalizationResolverTest extends Unit
{
    private const string GETTER = 'getLocalized';

    private const string SETTER = 'setLocalized';

    private KeyLocalizationResolver $resolver;

    public function _before(): void
    {
        $this->resolver = new KeyLocalizationResolver();
    }

    /**
     * The column is named once, so the accessors and the batch read of a listing page spell it the
     * same way.
     */
    public function testTheColumnIsNamedOnceAndSpelledLocalized(): void
    {
        $this->assertSame('localized', KeyLocalizationResolverInterface::LOCALIZED_COLUMN);
    }

    /**
     * Support is the core's answer and not a constant of this bundle, so the rule is asserted rather
     * than today's answer: it keeps holding on the day a core lands the column.
     */
    public function testSupportIsWhetherTheCoreDeclaresBothAccessors(): void
    {
        $this->assertSame(
            method_exists(KeyConfig::class, self::GETTER) && method_exists(KeyConfig::class, self::SETTER),
            $this->resolver->supportsLocalization()
        );
    }

    /**
     * @dataProvider flagProvider
     */
    public function testAStoredFlagIsReadBackAsItself(?bool $stored): void
    {
        $keyConfig = new LocalizationCapableKeyConfig();
        $keyConfig->localized = $stored;

        $this->assertSame($stored, $this->resolver->readFlag($keyConfig));
    }

    /**
     * @dataProvider flagProvider
     */
    public function testAWrittenFlagReachesTheModelAndReadsBack(?bool $written): void
    {
        $keyConfig = new LocalizationCapableKeyConfig();

        $this->resolver->writeFlag($keyConfig, $written);

        $this->assertSame($written, $keyConfig->localized);
        $this->assertSame($written, $this->resolver->readFlag($keyConfig));
    }

    /**
     * @return array<string, array{0: bool|null}>
     */
    public static function flagProvider(): array
    {
        return [
            'the key decides its own languages' => [true],
            'the key decides it is not localized' => [false],
            'the key states no flag' => [null],
        ];
    }

    /**
     * Null is a value of its own, so writing it over a stored boolean clears the key back to
     * inheriting rather than leaving the boolean where it was.
     */
    public function testWritingNullClearsAStoredBoolean(): void
    {
        $keyConfig = new LocalizationCapableKeyConfig();
        $keyConfig->localized = true;

        $this->resolver->writeFlag($keyConfig, null);

        $this->assertNull($keyConfig->localized);
        $this->assertNull($this->resolver->readFlag($keyConfig));
    }

    /**
     * A getter that hands the raw column over - a tinyint, say - states no flag rather than `true`:
     * the published flag is a tri-state and only a boolean is the key's own answer.
     */
    public function testANonBooleanIsReadAsNoFlag(): void
    {
        $keyConfig = new LocalizationCapableKeyConfig();
        $keyConfig->localized = 1;

        $this->assertNull($this->resolver->readFlag($keyConfig));
    }

    /**
     * The fallback, against a model with neither accessor: nothing is read and nothing is written,
     * and in particular the model is not reached for a method it does not carry.
     */
    public function testAModelWithoutTheAccessorsStatesNoFlagAndSwallowsAWrite(): void
    {
        $keyConfig = new LocalizationBlindKeyConfig();

        $this->resolver->writeFlag($keyConfig, true);

        $this->assertNull($this->resolver->readFlag($keyConfig));
    }

    /**
     * The same question against the real model, which is the one the bridge guards: on a core that
     * declares the accessors the flag written is the flag read back, and on one that does not it is
     * null either way - and no write raises an error.
     */
    public function testTheRealKeyConfigIsReadAndWrittenAccordingToWhatItSupports(): void
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');

        $this->resolver->setLocalized($keyConfig, true);

        $this->assertSame(
            $this->resolver->supportsLocalization() ? true : null,
            $this->resolver->getLocalized($keyConfig)
        );

        $this->resolver->setLocalized($keyConfig, null);

        $this->assertNull($this->resolver->getLocalized($keyConfig));
    }
}
