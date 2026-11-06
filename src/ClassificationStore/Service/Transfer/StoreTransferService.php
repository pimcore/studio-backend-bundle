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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Closure;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\StoreRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\ImportReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTriggerInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use function ctype_digit;
use function implode;
use function sprintf;

/**
 * @internal
 */
final readonly class StoreTransferService implements StoreTransferServiceInterface
{
    private const string NOT_FOUND_TYPE = 'store configuration';

    /**
     * The command a store is brought up to a full set of identifiers with, which is what an export
     * needs and what the refusal below points at.
     */
    private const string BACKFILL_COMMAND = 'pimcore:classificationstore:backfill-identifiers';

    public function __construct(
        private ArtifactBuilderInterface $artifactBuilder,
        private ArtifactParserInterface $artifactParser,
        private KeyRelationResolverInterface $keyRelationResolver,
        private ReconcilerInterface $reconciler,
        private SearchIndexRemapTriggerInterface $searchIndexRemapTrigger,
        private StoreRepositoryInterface $storeRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function exportStore(int $storeId): iterable
    {
        $store = $this->storeRepository->getById($storeId);
        $withoutIdentifier = $this->storeRepository->findEntitiesWithoutIdentifier($storeId);

        if ($withoutIdentifier !== []) {
            throw new ConflictException($this->describeIdentifierGaps($withoutIdentifier));
        }

        return $this->artifactBuilder->stream($store);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveStore(string $reference): StoreConfig
    {
        $store = $this->storeRepository->getByIdentifier($reference);

        if ($store !== null) {
            return $store;
        }

        if (ctype_digit($reference)) {
            return $this->storeRepository->getById((int) $reference);
        }

        throw new NotFoundException(self::NOT_FOUND_TYPE, $reference, 'identifier');
    }

    /**
     * {@inheritdoc}
     */
    public function importStore(int $storeId, string $json): ImportReport
    {
        $store = $this->storeRepository->getById($storeId);
        $document = $this->artifactParser->parse($json);

        $this->assertFileDescribesStore($store, $document);

        try {
            $report = $this->reconciler->apply($document, $store, false);
        } finally {
            $this->keyRelationResolver->clear();
            // one remap for the whole apply, however many of its keys changed the flag
            $this->searchIndexRemapTrigger->flush();
        }

        if ($report->hasConflicts()) {
            throw new ConflictException(
                'The configuration was not applied: ' . implode('; ', $report->getNamed(ApplyOutcome::CONFLICT))
            );
        }

        return $this->buildImportReport($report);
    }

    /**
     * {@inheritdoc}
     */
    public function applyArtifact(string $json, bool $dryRun, ?Closure $onDecision = null): ApplyReport
    {
        $document = $this->artifactParser->parse($json);
        $store = $this->storeRepository->getByIdentifier($document->getStoreIdentifier());

        try {
            return $this->reconciler->apply($document, $store, $dryRun, $onDecision);
        } finally {
            $this->keyRelationResolver->clear();
            // a dry run writes no key, so it notes nothing and this hands nothing over
            $this->searchIndexRemapTrigger->flush();
        }
    }

    /**
     * The file has to describe the store of the route: one that carries another identifier, or one
     * whose identifier names a different store, is a configuration of somewhere else.
     *
     * @throws ConflictException
     */
    private function assertFileDescribesStore(StoreConfig $store, ArtifactDocument $document): void
    {
        $identifier = $document->getStoreIdentifier();
        $carried = $store->getIdentifier();

        if ($carried !== null && $carried !== $identifier) {
            throw new ConflictException(
                sprintf('The file describes the store "%s", which is not the store it was sent to.', $identifier)
            );
        }

        $named = $this->storeRepository->getByIdentifier($identifier);

        if ($named !== null && $named->getId() !== $store->getId()) {
            throw new ConflictException(
                sprintf('The store "%s" the file describes already exists as another store.', $identifier)
            );
        }
    }

    /**
     * @param list<array{level: string, name: string}> $withoutIdentifier
     */
    private function describeIdentifierGaps(array $withoutIdentifier): string
    {
        $named = [];
        foreach ($withoutIdentifier as $entity) {
            $named[] = sprintf('%s "%s"', $entity['level'], $entity['name']);
        }

        return sprintf(
            'The store cannot be exported while %s carry no identifier. Run "%s" first.',
            implode(', ', $named),
            self::BACKFILL_COMMAND
        );
    }

    private function buildImportReport(ApplyReport $report): ImportReport
    {
        return new ImportReport(
            created: $report->getCount(ApplyOutcome::CREATED),
            updated: $report->getCount(ApplyOutcome::UPDATED),
            adopted: $report->getCount(ApplyOutcome::ADOPTED),
            matchedUnchanged: $report->getCount(ApplyOutcome::MATCHED_UNCHANGED),
            targetOnly: $report->getCount(ApplyOutcome::TARGET_ONLY),
            createdEntries: $report->getNamed(ApplyOutcome::CREATED),
            updatedEntries: $report->getNamed(ApplyOutcome::UPDATED),
            adoptedEntries: $report->getNamed(ApplyOutcome::ADOPTED),
            targetOnlyEntries: $report->getNamed(ApplyOutcome::TARGET_ONLY),
            conflicts: $report->getNamed(ApplyOutcome::CONFLICT),
        );
    }
}
