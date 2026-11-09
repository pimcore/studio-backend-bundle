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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValue;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializer;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use function array_map;
use function in_array;

/**
 * The production bridge between a write of qualified values and the engine of the store the key
 * belongs to, which this bundle does not own.
 *
 * @internal
 */
final class StoreEngineResolverTest extends Unit
{
    /**
     * The two capability names a write is judged against are spelled the way the core enum spells
     * its cases, asserted rather than restated, so that a rename in core shows up here.
     */
    public function testTheCapabilityNamesAreTheCoreEnumsOwnSpelling(): void
    {
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
        $this->assertSame([], (new StoreEngineResolver())->featuresOf($this->createEngine([])));
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
        $serializer = new QualifiedValueSerializer(new KeyQualifierResolver());
        $needsVariants = [new QualifiedValue('80', 'imperial')];

        // and only such a write: the single default slot goes through against either answer
        $serializer->assertSupported([new QualifiedValue('80')], []);
        $serializer->assertSupported([new QualifiedValue('80')], null);

        $this->assertTrue($this->isRefused($serializer, $needsVariants, []));
        $this->assertTrue($this->isRefused($serializer, $needsVariants, null));
    }

    /**
     * Whether the write path refused the values against the capability answer handed over.
     *
     * @param QualifiedValue[] $values
     * @param string[]|null $capabilities
     */
    private function isRefused(
        QualifiedValueSerializer $serializer,
        array $values,
        ?array $capabilities
    ): bool {
        try {
            $serializer->assertSupported($values, $capabilities);
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
