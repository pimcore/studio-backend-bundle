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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\Transfer;

use Closure;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyTypeEngineFitResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTriggerInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyContext;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyDiagnostics;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyRefusals;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryWriter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactRelationWriter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactTargetMatcher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function sprintf;

/**
 * The walk-and-claim half of the writer: which entry each row of a level answers for, and what
 * becomes of a row that answers for nothing. The write itself is the caller's closure here, so what
 * a test asserts on is the set of calls the writer made - a call that was not made is a write no
 * model ever saw.
 *
 * @internal
 */
final class ArtifactEntryWriterTest extends Unit
{
    private const int STORE_ID = 1;

    /**
     * @var list<string>
     */
    private array $calls = [];

    /**
     * The row claims the entry its identifier names, and an entry no row claimed is handed back with
     * no row in its place - which is what makes it a creation.
     */
    public function testAClaimedRowIsWrittenWithItsEntryAndAnUnclaimedEntryWithNone(): void
    {
        $writer = $this->createWriter();
        $context = $this->createContext();

        $writer->reconcileWalkedTargets(
            $context,
            ArtifactSection::GROUPS,
            [
                '01A' => ['identifier' => '01A', 'name' => 'Alpha'],
                '01B' => ['identifier' => '01B', 'name' => 'Beta'],
            ],
            [$this->walkOf($this->createGroup(1, '01A', 'Alpha'))],
            $this->recordingWrite()
        );

        $this->assertSame(['1:01A', 'none:01B'], $this->calls);
    }

    /**
     * A row reached twice is silent. Stamping an identifier onto an adopted row moves it into the
     * walk that follows, where the entry it has already answered for must not read as somebody
     * else's.
     */
    public function testARowReachedTwiceIsWrittenOnceAndReportedNowhere(): void
    {
        $writer = $this->createWriter();
        $context = $this->createContext();
        $row = $this->createGroup(1, '01A', 'Alpha');

        $writer->reconcileWalkedTargets(
            $context,
            ArtifactSection::GROUPS,
            ['01A' => ['identifier' => '01A', 'name' => 'Alpha']],
            [$this->walkOf($row), $this->walkOf($row)],
            $this->recordingWrite()
        );

        $this->assertSame(['1:01A'], $this->calls);
        $this->assertSame(0, $context->getReport()->getCount(ApplyOutcome::TARGET_ONLY));
    }

    /**
     * A namesake of a row that already claimed the entry is left in place as target only, rather
     * than adopted onto the entry its identifier owner answers for.
     */
    public function testANamesakeOfAClaimedEntryIsLeftAsTargetOnly(): void
    {
        $writer = $this->createWriter();
        $context = $this->createContext();

        $writer->reconcileWalkedTargets(
            $context,
            ArtifactSection::GROUPS,
            ['01A' => ['identifier' => '01A', 'name' => 'Alpha']],
            [
                $this->walkOf($this->createGroup(1, '01A', 'Alpha')),
                $this->walkOf($this->createGroup(2, null, 'Alpha')),
            ],
            $this->recordingWrite()
        );

        $this->assertSame(['1:01A'], $this->calls);
        $this->assertSame(1, $context->getReport()->getCount(ApplyOutcome::TARGET_ONLY));
        $this->assertSame(['group "Alpha"'], $context->getReport()->getNamed(ApplyOutcome::TARGET_ONLY));
    }

    /**
     * A row the section says nothing about is left in place too, and nothing is written for it.
     */
    public function testARowNoEntryDescribesIsLeftAsTargetOnly(): void
    {
        $writer = $this->createWriter();
        $context = $this->createContext();

        $writer->reconcileWalkedTargets(
            $context,
            ArtifactSection::GROUPS,
            [],
            [$this->walkOf($this->createGroup(1, '01A', 'Alpha'))],
            $this->recordingWrite()
        );

        $this->assertSame([], $this->calls);
        $this->assertSame(1, $context->getReport()->getCount(ApplyOutcome::TARGET_ONLY));
    }

    /**
     * A key whose type the engine of the store cannot hold is one conflict naming the key, the type
     * and the engine, and the key repository is never reached: the writer is the refusal's caller.
     */
    public function testAKeyTheEngineCannotHoldIsOneConflictNamingKeyTypeAndEngine(): void
    {
        $context = $this->createContext();

        $this->createWriter('eav')->writeKey($context, null, $this->createKeyEntry('geopoint'));

        $this->assertSame(
            ['key "position": the field type "geopoint" cannot be held by the "eav" storage engine of the store'],
            $context->getReport()->getNamed(ApplyOutcome::CONFLICT)
        );
        $this->assertSame(0, $context->getReport()->getCount(ApplyOutcome::CREATED));
    }

    /**
     * An engine that holds the type, and a type every engine holds, refuse nothing.
     */
    public function testAKeyTheEngineHoldsIsNotRefused(): void
    {
        $context = new ApplyContext(true, new ApplyReport());
        $context->setStoreId(self::STORE_ID);

        $this->createWriter(null)->writeKey($context, null, $this->createKeyEntry('geopoint'));
        $this->createWriter('eav')->writeKey($context, null, $this->createKeyEntry('input'));

        $this->assertSame(0, $context->getReport()->getCount(ApplyOutcome::CONFLICT));
        $this->assertSame(2, $context->getReport()->getCount(ApplyOutcome::CREATED));
    }

    /**
     * A key the store already carries exactly as the file describes it is not written, so it is not
     * refused either, whatever its stored type.
     */
    public function testAKeyTheStoreAlreadyCarriesAsDescribedIsLeftAloneWhateverItsType(): void
    {
        $context = $this->createContext();
        $entry = $this->createKeyEntry('geopoint');
        $target = new KeyConfig();
        $target->setId(7);
        $target->setIdentifier('01KEY');
        $target->setName('position');
        $target->setType('geopoint');
        $target->setEnabled(true);
        $target->setDefinition('{"fieldtype":"geopoint"}');

        $this->createWriter('eav')->writeKey($context, $target, $entry);

        $this->assertSame(0, $context->getReport()->getCount(ApplyOutcome::CONFLICT));
        $this->assertSame(1, $context->getReport()->getCount(ApplyOutcome::MATCHED_UNCHANGED));
    }

    /**
     * @return array<string, mixed>
     */
    private function createKeyEntry(string $type): array
    {
        return [
            'identifier' => '01KEY',
            'name' => $type === 'geopoint' ? 'position' : 'weight',
            'title' => null,
            'description' => null,
            'type' => $type,
            'definition' => ['fieldtype' => $type],
            'enabled' => true,
        ];
    }

    private function recordingWrite(): Closure
    {
        return function (?GroupConfig $target, array $entry): void {
            $this->calls[] = sprintf(
                '%s:%s',
                $target === null ? 'none' : (string) $target->getId(),
                (string) $entry['identifier']
            );
        };
    }

    /**
     * One chunk holding the given rows, then nothing: the walk is called with the cursor and the
     * chunk size, and a chunk shorter than the chunk size ends it.
     */
    private function walkOf(GroupConfig ...$rows): Closure
    {
        return static fn (int $afterId, int $limit): array => $afterId === 0 ? $rows : [];
    }

    private function createContext(): ApplyContext
    {
        $context = new ApplyContext(false, new ApplyReport());
        $context->setStoreId(self::STORE_ID);

        return $context;
    }

    private function createGroup(int $id, ?string $identifier, string $name): GroupConfig
    {
        $config = new GroupConfig();
        $config->setId($id);
        $config->setStoreId(self::STORE_ID);
        $config->setName($name);

        if ($identifier !== null) {
            $config->setIdentifier($identifier);
        }

        return $config;
    }

    private function createWriter(?string $refusingEngine = null): ArtifactEntryWriter
    {
        $parser = new ArtifactParser();
        $matcher = new ArtifactTargetMatcher(new InMemoryKeyLocalizationResolver());
        $diagnostics = new ApplyDiagnostics();
        $refusals = new ApplyRefusals(
            $this->makeEmpty(CollectionRepositoryInterface::class),
            $this->makeEmpty(GroupRepositoryInterface::class),
            $this->makeEmpty(KeyGroupRelationRepositoryInterface::class),
            $this->makeEmpty(KeyTypeEngineFitResolverInterface::class, [
                'findRefusingEngine' => static fn (string $type): ?string => $type === 'geopoint'
                    ? $refusingEngine
                    : null,
            ]),
            $this->makeEmpty(SubGroupRepositoryInterface::class)
        );

        return new ArtifactEntryWriter(
            $parser,
            $matcher,
            $diagnostics,
            $refusals,
            new ArtifactRelationWriter(
                $parser,
                $matcher,
                $diagnostics,
                $refusals,
                $this->makeEmpty(CollRelationRepositoryInterface::class),
                $this->makeEmpty(KeyGroupRelationRepositoryInterface::class)
            ),
            $this->makeEmpty(KeyRepositoryInterface::class),
            $this->makeEmpty(SearchIndexRemapTriggerInterface::class)
        );
    }
}
