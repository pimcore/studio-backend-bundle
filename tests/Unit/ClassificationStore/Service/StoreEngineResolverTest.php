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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\EngineCapabilityGuard;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValue;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use function array_map;
use function enum_exists;
use function in_array;
use function interface_exists;

/**
 * The production bridge between the engine of a classification store, which this bundle does not
 * own, and the two things that ask about it: what the reporting surfaces publish, and what a write
 * of qualified values is judged against.
 *
 * What the bridge answers, and - the part that matters to a client - which of its answers are the
 * same answer. `null` is not "this engine supports nothing": it is every case where the question
 * could not be put, and a client must not read core support out of it.
 *
 * @internal
 */
final class StoreEngineResolverTest extends Unit
{
    public function testACoreWithoutTheEngineColumnReportsNoEngine(): void
    {
        $resolver = new StoreEngineResolver(null);

        $this->assertNull(
            $resolver->readEngine(new EngineBlindStoreConfig()),
            'a store configuration that declares no getEngine() answers nothing, never a guess'
        );
    }

    public function testTheStoredEngineIsReportedVerbatim(): void
    {
        $storeConfig = new EngineAwareStoreConfig();
        $storeConfig->engine = 'document';

        $this->assertSame(
            'document',
            (new StoreEngineResolver(null))->readEngine($storeConfig),
            'the engine is reported as the store stores it, not normalised'
        );
    }

    public function testAnEngineThatIsNotAStringIsNoEngine(): void
    {
        $storeConfig = new EngineAwareStoreConfig();
        $storeConfig->engine = 42;

        $this->assertNull(
            (new StoreEngineResolver(null))->readEngine($storeConfig),
            'a column holding something that is not an engine name answers nothing'
        );
    }

    public function testTheDefaultEngineIsNullRatherThanANameOfItsOwn(): void
    {
        $storeConfig = new EngineAwareStoreConfig();
        $storeConfig->engine = null;

        $this->assertNull(
            (new StoreEngineResolver(null))->readEngine($storeConfig),
            'a store on the default engine stores no engine, and that is what is published'
        );
    }

    /**
     * The two capability names a write is judged against are spelled the way the core enum spells
     * its cases, asserted rather than restated, so that a rename in core shows up here.
     */
    public function testTheCapabilityNamesAreTheCoreEnumsOwnSpelling(): void
    {
        $this->requireCoreEngineSupport();

        $cases = array_map(static fn (EngineFeature $feature): string => $feature->name, EngineFeature::cases());

        $this->assertTrue(in_array(StoreEngineResolverInterface::FEATURE_MULTI_VALUE, $cases, true));
        $this->assertTrue(in_array(StoreEngineResolverInterface::FEATURE_VARIANTS, $cases, true));
    }

    /**
     * Exactly those cases the engine answers true for, in the order the core enum declares them.
     *
     * @throws Exception
     */
    public function testExactlyTheReportedCapabilitiesAreAnswered(): void
    {
        $this->requireCoreEngineSupport();

        $capabilities = (new StoreEngineResolver())->featuresOf(
            $this->createEngine([EngineFeature::Variants, EngineFeature::MultiValue])
        );

        $this->assertSame(
            [
                StoreEngineResolverInterface::FEATURE_MULTI_VALUE,
                StoreEngineResolverInterface::FEATURE_VARIANTS,
            ],
            $capabilities
        );
    }

    /**
     * An engine that reports no capability answers with the empty set, which is not the same answer
     * as a core that could not be asked at all.
     *
     * @throws Exception
     */
    public function testAnEngineReportingNothingAnswersWithTheEmptySet(): void
    {
        $this->requireCoreEngineSupport();

        $this->assertSame([], (new StoreEngineResolver())->featuresOf($this->createEngine([])));
    }

    /**
     * The three assertions above are about an engine, and a core without engine support has none to
     * be about: `composer.json` allows such a core and the bundle is wired for it - the engine
     * resolver is an optional dependency (`config/classification_store.yaml`) and
     * {@see StoreEngineResolver::featuresOf()} asks `enum_exists()` before it reads a case - so
     * requiring the two core symbols unconditionally would fail this suite on a core the bundle
     * supports.
     *
     * Only those three are guarded. What the bridge answers *without* an engine is the half that
     * such a core actually runs, and it stays asserted: the engine-blind store configuration, the
     * null answer of a core carrying no engine resolver, and the write path's fail-closed verdict
     * on both answers.
     */
    private function requireCoreEngineSupport(): void
    {
        if (!enum_exists(EngineFeature::class) || !interface_exists(EngineInterface::class)) {
            $this->markTestSkipped('The core does not ship Classification Store engine support.');
        }
    }

    /**
     * No engine resolver to ask is no answer rather than no capabilities, so a core that does not
     * ship one answers null - and the write path refuses on it, which is the sibling assertion
     * below.
     */
    public function testACoreWithoutAnEngineResolverAnswersNothing(): void
    {
        $this->assertNull((new StoreEngineResolver())->getCapabilitiesOfStore(1));
    }

    /**
     * What the two answers this resolver can give mean to the write path, asserted by feeding each
     * to the thing that acts on them rather than on their own.
     *
     * Both refuse a write needing an unconfirmed capability: the empty set, which an engine
     * reporting no capability answers with, and null, which a core carrying no engine resolver at
     * all answers with. The third answer, an engine that could not be resolved, is neither of these
     * and never reaches here - it stays a failure
     * ({@see StoreEngineResolverInterface::getCapabilitiesOfStore()}), because the write would
     * otherwise be judged on a verdict nobody reached.
     *
     * @throws Exception
     */
    public function testNeitherAnswerLetsAWriteNeedingAnUnconfirmedCapabilityThrough(): void
    {
        $guard = new EngineCapabilityGuard();
        $needsVariants = [new QualifiedValue('80', 'imperial')];

        // and only such a write: the single default slot goes through against either answer
        $guard->assertSupported([new QualifiedValue('80')], []);
        $guard->assertSupported([new QualifiedValue('80')], null);

        $this->assertTrue($this->isRefused($guard, $needsVariants, []));
        $this->assertTrue($this->isRefused($guard, $needsVariants, null));
    }

    /**
     * Whether the write path refused the values against the capability answer handed over.
     *
     * @param QualifiedValue[] $values
     * @param string[]|null $capabilities
     */
    private function isRefused(
        EngineCapabilityGuard $guard,
        array $values,
        ?array $capabilities
    ): bool {
        try {
            $guard->assertSupported($values, $capabilities);
        } catch (AbstractApiException) {
            return true;
        }

        return false;
    }

    /**
     * @param EngineFeature[] $supported
     *
     * @throws Exception
     */
    private function createEngine(array $supported): EngineInterface
    {
        return $this->makeEmpty(EngineInterface::class, [
            'supports' => static fn (EngineFeature $feature): bool => in_array($feature, $supported, true),
        ]);
    }
}
