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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ReconcilerInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreTransferService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
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

    public function testImportingAFileWhoseStoreIdentifierNamesAnotherStoreIsRefused(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getById')->willReturn($this->createStore(self::STORE_ID, identifier: null));
        $storeRepository
            ->method('getByIdentifier')
            ->willReturn($this->createStore(self::OTHER_STORE_ID));

        $this->expectException(ConflictException::class);

        $this->createService($storeRepository)->importStore(self::STORE_ID, $this->createArtifactJson());
    }

    /**
     * The store of the route carries an identifier of its own, so a file describing a different one
     * is a configuration of somewhere else even when no store carries that identifier yet.
     */
    public function testImportingAFileIntoAStoreThatCarriesAnotherIdentifierIsRefused(): void
    {
        $storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $storeRepository->method('getById')->willReturn($this->createStore(self::STORE_ID, '01ELSEWHERE'));
        $storeRepository->method('getByIdentifier')->willReturn(null);

        $this->expectException(ConflictException::class);

        $this->createService($storeRepository)->importStore(self::STORE_ID, $this->createArtifactJson());
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
        $applyReport->record(ApplyOutcome::CONFLICT, 'first refusal');
        $applyReport->record(ApplyOutcome::CONFLICT, 'second refusal');

        try {
            $this
                ->createService($storeRepository, $applyReport)
                ->importStore(self::STORE_ID, $this->createArtifactJson());
            $this->fail('A refused configuration cannot answer with a report.');
        } catch (ConflictException $e) {
            $this->assertStringContainsString('first refusal', $e->getMessage());
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
        $reconciler->method('apply')->willReturn($applyReport ?? new ApplyReport());

        return new StoreTransferService(
            $this->makeEmpty(ArtifactBuilderInterface::class, ['stream' => []]),
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

    private function createArtifactJson(): string
    {
        return json_encode([
            'store' => ['identifier' => self::STORE_IDENTIFIER, 'name' => 'ETIM', 'description' => null],
            'subGroups' => [],
            'collections' => [],
            'groups' => [],
            'keys' => [],
            'collectionGroupRelations' => [],
            'keyGroupRelations' => [],
        ], JSON_THROW_ON_ERROR);
    }
}
