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

use const JSON_THROW_ON_ERROR;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\StoreRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTriggerInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactBuilderInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactDocument;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactDuplicator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\IdentifierGeneratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ReconcilerInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreTransferService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use function array_keys;
use function json_encode;

/**
 * The service is where the HTTP answers are decided: a store no id carries, a store that cannot be
 * exported, and a file that describes somewhere else.
 *
 * @internal
 */
final class StoreTransferServiceTest extends Unit
{
    private const int STORE_ID = 1;

    private const int OTHER_STORE_ID = 2;

    private const string STORE_IDENTIFIER = '01STORE';

    private const string BACKFILL_COMMAND = 'pimcore:classificationstore:backfill-identifiers';

    private const string GROUP_ENTRY = 'group "Measurements"';

    private const string GENERATED_IDENTIFIER = '01GENERATED';

    private const string FOREIGN_STORE_IDENTIFIER = '01ELSEWHERE';

    private const string GROUP_IDENTIFIER = '01GROUP';

    private const string GROUP_NAME = 'Measurements';

    private const string FIRST_REFUSAL = 'first refusal';

    /**
     * The document the reconciler was handed, and whether it was told to replace: the service's two
     * decisions, which nothing else in the report distinguishes.
     */
    private ?ArtifactDocument $applied = null;

    private bool $replaced = false;

    public function testExportingAStoreIdNoStoreCarriesIsNotFound(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository
            ->method('getById')
            ->willThrowException(new NotFoundException('store configuration', 404));

        $this->expectException(NotFoundException::class);

        $this->createService($storeRepository)->exportStore(404);
    }

    /**
     * An entry without an identifier has nothing a file could match it by on the way back in. The
     * refusal names every one of them and says what to run first, so the operator does not have to
     * find them one export at a time.
     */
    public function testExportingAStoreWithIdentifierGapsNamesEveryOneOfThem(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getById')->willReturn($this->createStore(self::STORE_ID));
        $storeRepository->method('findEntitiesWithoutIdentifier')->willReturn([
            ['level' => 'group', 'name' => 'Measurements'],
            ['level' => 'key', 'name' => 'weight'],
        ]);

        try {
            $this->createService($storeRepository)->exportStore(self::STORE_ID);
            $this->fail('A store with identifier gaps cannot be exported.');
        } catch (ConflictException $e) {
            $this->assertStringContainsString(self::GROUP_ENTRY, $e->getMessage());
            $this->assertStringContainsString('key "weight"', $e->getMessage());
            $this->assertStringContainsString(self::BACKFILL_COMMAND, $e->getMessage());
        }
    }

    public function testImportingAFileThatIsNotParseableIsUnprocessable(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getById')->willReturn($this->createStore(self::STORE_ID));

        $this->expectException(InvalidArgumentException::class);

        $this->createService($storeRepository)->importStore(self::STORE_ID, '{"store": ');
    }

    /**
     * The store of the route carries no identifier, but the file's belongs to another store, so the
     * row in front of the operator is not the one the file is about: it is imported as a duplicate
     * rather than adopted onto an identity that is taken.
     */
    public function testImportingAFileWhoseStoreIdentifierNamesAnotherStoreIsADuplicate(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getById')->willReturn($this->createStore(self::STORE_ID, identifier: null));
        $storeRepository
            ->method('getByIdentifier')
            ->willReturn($this->createStore(self::OTHER_STORE_ID));

        $report = $this->createService($storeRepository)->importStore(
            self::STORE_ID,
            $this->createArtifactJson()
        );

        $this->assertTrue($this->replaced);
        $this->assertTrue($report->isReplaced());
    }

    /**
     * The store of the route carries an identifier of its own, so a file describing a different one
     * is a configuration of somewhere else even when no store carries that identifier yet - and it
     * is imported as a duplicate, with every member identifier regenerated.
     */
    public function testImportingAFileIntoAStoreThatCarriesAnotherIdentifierIsADuplicate(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository
            ->method('getById')
            ->willReturn($this->createStore(self::STORE_ID, self::FOREIGN_STORE_IDENTIFIER));
        $storeRepository->method('getByIdentifier')->willReturn(null);

        $report = $this->createService($storeRepository)->importStore(
            self::STORE_ID,
            $this->createArtifactJson(true)
        );

        $this->assertTrue($report->isReplaced());
        $this->assertTrue($this->replaced);
        $this->assertSame(
            [self::GENERATED_IDENTIFIER],
            array_keys($this->applied?->getEntries(ArtifactSection::GROUPS) ?? [])
        );
    }

    /**
     * A file describing the store it was sent to is restored into it, unchanged: no duplicate, no
     * removal, and the identifiers the file carries are the ones that are written.
     */
    public function testImportingAFileDescribingItsOwnStoreIsNotADuplicate(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getById')->willReturn($this->createStore(self::STORE_ID));
        $storeRepository->method('getByIdentifier')->willReturn($this->createStore(self::STORE_ID));

        $report = $this->createService($storeRepository)->importStore(
            self::STORE_ID,
            $this->createArtifactJson(true)
        );

        $this->assertFalse($report->isReplaced());
        $this->assertFalse($this->replaced);
        $this->assertSame(
            [self::GROUP_IDENTIFIER],
            array_keys($this->applied?->getEntries(ArtifactSection::GROUPS) ?? [])
        );
    }

    /**
     * The console apply names its target by the file's own store identifier and reports rather than
     * throws, so that the command decides what a refused run exits with.
     */
    public function testTheConsoleApplyAnswersWithTheReportItWasGiven(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getByIdentifier')->willReturn(null);

        $applyReport = new ApplyReport();
        $applyReport->record(ApplyOutcome::CREATED, self::GROUP_ENTRY);
        $applyReport->record(ApplyOutcome::CONFLICT, self::FIRST_REFUSAL);

        $report = $this
            ->createService($storeRepository, $applyReport)
            ->applyArtifact($this->createArtifactJson(), false);

        $this->assertFalse($this->replaced);
        $this->assertSame(1, $report->getCount(ApplyOutcome::CREATED));
        $this->assertSame([self::FIRST_REFUSAL], $report->getNamed(ApplyOutcome::CONFLICT));
    }

    public function testImportingAnsweringWithTheReportOfWhatWasDecided(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getById')->willReturn($this->createStore(self::STORE_ID));
        $storeRepository->method('getByIdentifier')->willReturn($this->createStore(self::STORE_ID));

        $applyReport = new ApplyReport();
        $applyReport->record(ApplyOutcome::CREATED, self::GROUP_ENTRY);
        $applyReport->record(ApplyOutcome::TARGET_ONLY, 'group "Logistics"');

        $report = $this
            ->createService($storeRepository, $applyReport)
            ->importStore(self::STORE_ID, $this->createArtifactJson());

        $this->assertSame(1, $report->getCreated());
        $this->assertSame(1, $report->getTargetOnly());
        $this->assertSame([self::GROUP_ENTRY], $report->getCreatedEntries());
        $this->assertSame(['group "Logistics"'], $report->getTargetOnlyEntries());
        $this->assertSame([], $report->getConflicts());
    }

    /**
     * A refused apply answers 409 with every conflict it found, which is what the error message is
     * for over HTTP.
     */
    public function testImportingARefusedConfigurationAnswersWithEveryConflict(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getById')->willReturn($this->createStore(self::STORE_ID));
        $storeRepository->method('getByIdentifier')->willReturn($this->createStore(self::STORE_ID));

        $applyReport = new ApplyReport();
        $applyReport->record(ApplyOutcome::CONFLICT, self::FIRST_REFUSAL);
        $applyReport->record(ApplyOutcome::CONFLICT, 'second refusal');

        try {
            $this
                ->createService($storeRepository, $applyReport)
                ->importStore(self::STORE_ID, $this->createArtifactJson());
            $this->fail('A refused configuration cannot answer with a report.');
        } catch (ConflictException $e) {
            $this->assertStringContainsString(self::FIRST_REFUSAL, $e->getMessage());
            $this->assertStringContainsString('second refusal', $e->getMessage());
        }
    }

    public function testAStoreIsResolvedByIdentifierForTheConsole(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getByIdentifier')->willReturn($this->createStore(self::STORE_ID));

        $store = $this->createService($storeRepository)->resolveStore(self::STORE_IDENTIFIER);

        $this->assertSame(self::STORE_ID, $store->getId());
    }

    /**
     * An identifier is `/^[A-Za-z0-9][A-Za-z0-9_-]*$/`, so a store may carry one made of digits
     * alone. Reading such a reference as a row id would export the store that happens to sit at
     * that id - a different store, under a reference the operator used because it is the one thing
     * that identifies a configuration across installations.
     */
    public function testADigitsOnlyIdentifierResolvesToItsOwnStoreAndNotToThatRowId(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository
            ->method('getByIdentifier')
            ->with('1')
            ->willReturn($this->createStore(self::OTHER_STORE_ID, '1'));
        $storeRepository
            ->expects($this->never())
            ->method('getById');

        $store = $this->createService($storeRepository)->resolveStore('1');

        $this->assertSame(self::OTHER_STORE_ID, $store->getId());
    }

    /**
     * A numeric reference no identifier claims is still the row id it has always been, which is what
     * keeps a store with no identifier exportable by id.
     */
    public function testANumericReferenceNoIdentifierClaimsIsStillARowId(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getByIdentifier')->willReturn(null);
        $storeRepository
            ->expects($this->once())
            ->method('getById')
            ->with(self::STORE_ID)
            ->willReturn($this->createStore(self::STORE_ID));

        $store = $this->createService($storeRepository)->resolveStore((string) self::STORE_ID);

        $this->assertSame(self::STORE_ID, $store->getId());
    }

    public function testAnUnknownStoreIdentifierIsNotFound(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getByIdentifier')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->createService($storeRepository)->resolveStore('01NOBODY');
    }

    private function createService(
        StoreRepositoryInterface $storeRepository,
        ?ApplyReport $applyReport = null,
    ): StoreTransferService {
        $reconciler = $this->createMock(ReconcilerInterface::class);
        $reconciler->method('apply')->willReturnCallback(
            function (
                ArtifactDocument $document,
                ?StoreConfig $store,
                bool $dryRun,
                bool $replace = false
            ) use ($applyReport): ApplyReport {
                $this->applied = $document;
                $this->replaced = $replace;

                return $applyReport ?? new ApplyReport();
            }
        );

        return new StoreTransferService(
            $this->makeEmpty(ArtifactBuilderInterface::class, ['stream' => []]),
            new ArtifactDuplicator(
                $this->makeEmpty(IdentifierGeneratorInterface::class, ['generate' => self::GENERATED_IDENTIFIER])
            ),
            new ArtifactParser(),
            $this->makeEmpty(KeyRelationResolverInterface::class),
            $reconciler,
            $this->makeEmpty(SearchIndexRemapTriggerInterface::class),
            $storeRepository
        );
    }

    private function createStore(int $id, ?string $identifier = self::STORE_IDENTIFIER): StoreConfig
    {
        $store = new StoreConfig();
        $store->setId($id);
        $store->setName('ETIM');

        if ($identifier !== null) {
            $store->setIdentifier($identifier);
        }

        return $store;
    }

    /**
     * @return array<string, mixed>
     */
    private function createEntry(string $identifier, string $name): array
    {
        return [
            ArtifactField::IDENTIFIER->value => $identifier,
            ArtifactField::NAME->value => $name,
            ArtifactField::DESCRIPTION->value => null,
        ];
    }

    private function createArtifactJson(bool $withGroup = false): string
    {
        return json_encode([
            'store' => $this->createEntry(self::STORE_IDENTIFIER, 'ETIM'),
            'subGroups' => [],
            'collections' => [],
            'groups' => $withGroup ? [$this->createEntry(self::GROUP_IDENTIFIER, self::GROUP_NAME)] : [],
            'keys' => [],
            'collectionGroupRelations' => [],
            'keyGroupRelations' => [],
        ], JSON_THROW_ON_ERROR);
    }
}
