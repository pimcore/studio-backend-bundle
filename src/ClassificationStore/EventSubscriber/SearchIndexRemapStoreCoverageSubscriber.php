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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapQueueInterface;
use Pimcore\Event\DataObjectClassDefinitionEvents;
use Pimcore\Event\FieldcollectionDefinitionEvents;
use Pimcore\Event\ObjectbrickDefinitionEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use function sprintf;

/**
 * Supersedes the stores' coverage claims whenever a definition write changes which classes a store
 * can reach.
 *
 * A claim ({@see SearchIndexRemapQueueInterface::claimStoreCoverage()}) says "every class this store
 * reaches has a remap pending". That is answered against the definitions as they stood when the
 * claim was made, and a definition write is precisely what moves a class in or out of the set: a
 * class gaining a classification store field for this store, a brick or a field collection gaining
 * one - the walk that resolves a store to its classes reaches all three
 * ({@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTargetResolver}) -
 * or any of them losing one. Left standing, the claim skips the class that joined, for as long as
 * the already-pending one keeps the claim alive, and that class's mapping is left stale.
 *
 * All three definition families are listened to, on add, update and delete alike, because all three
 * are read by that walk and a store's set of classes is no more stable under one of them than
 * another. Add and delete are not redundant with update: a brick created with a field of the store
 * never has an update, and a class deleted out of the set leaves a claim covering a class that is
 * gone.
 *
 * Every claim is superseded, not the ones the saved definition reaches. Which stores a definition
 * reaches is what the write itself decides, so neither the state before it nor the state after it
 * is the set to invalidate - and the cost of superseding them all is one store resolve on the next
 * flush, where the cost of superseding too few is the stale mapping.
 *
 * Superseded rather than deleted, because a claim is written after the classes it covers were read
 * ({@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTrigger::flush()}):
 * a deletion here reaches the claims that exist, and the one being written out of a resolve that
 * began before this write would land behind it and outlive it. Moving the definition generation on
 * answers that claim away too, whenever it arrives
 * ({@see SearchIndexRemapQueueInterface::invalidateStoreCoverage()}).
 *
 * Nothing here may fail the definition save it runs after. The save is committed by then, and a
 * settings store that cannot be reached has to cost a log line rather than the editor's class.
 *
 * @internal
 */
final readonly class SearchIndexRemapStoreCoverageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SearchIndexRemapQueueInterface $searchIndexRemapQueue,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectClassDefinitionEvents::POST_ADD => 'onDefinitionWritten',
            DataObjectClassDefinitionEvents::POST_UPDATE => 'onDefinitionWritten',
            DataObjectClassDefinitionEvents::POST_DELETE => 'onDefinitionWritten',
            ObjectbrickDefinitionEvents::POST_ADD => 'onDefinitionWritten',
            ObjectbrickDefinitionEvents::POST_UPDATE => 'onDefinitionWritten',
            ObjectbrickDefinitionEvents::POST_DELETE => 'onDefinitionWritten',
            FieldcollectionDefinitionEvents::POST_ADD => 'onDefinitionWritten',
            FieldcollectionDefinitionEvents::POST_UPDATE => 'onDefinitionWritten',
            FieldcollectionDefinitionEvents::POST_DELETE => 'onDefinitionWritten',
        ];
    }

    public function onDefinitionWritten(): void
    {
        try {
            $this->searchIndexRemapQueue->invalidateStoreCoverage();
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to supersede the classification store search index coverage claims: %s',
                $e->getMessage()
            ));
        }
    }
}
