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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Tool\SettingsStoreResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Messenger\SearchIndexRemapMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use function time;

/**
 * Hands a search index remap of one data object class to the queue and answers whether one is
 * already waiting there. The mapping is rewritten and the elements are re-indexed by the handler of
 * the queued message ({@see SearchIndexRemapMessage}), never in the request that writes the key.
 *
 * The marker that says "queued" is this bundle's own, set here and removed by that handler, because
 * the only thing a message in a transport can be asked about is nothing: the rebuild marker of the
 * generic data index bundle is set when a rebuild starts being queued, which is after a worker has
 * picked the message up, and would leave every change made before that un-coalesced.
 *
 * The marker is set after the hand-over and read before it, so two requests racing cost one extra
 * remap at worst, while the reverse order could leave a marker with no message behind it and
 * suppress every later change - the silent no-op this exists to prevent. The marker carries the
 * time it was written and stops answering once that is old enough, so even a message the handler
 * never sees costs a window rather than every remap of its class from then on.
 *
 * @internal
 */
final readonly class SearchIndexRemapQueue implements SearchIndexRemapQueueInterface
{
    /**
     * Own scope, so the id of the entry can be the data object class id itself without sharing a
     * name space with anything else stored per class.
     */
    private const string SETTINGS_STORE_SCOPE = 'studio_classificationstore_index_remap';

    private const string SETTINGS_STORE_TYPE = 'int';

    /**
     * How long a marker is taken to mean "queued". It is removed by the handler, so this is only
     * ever reached when the message never got there - a transport that dropped it, retries that ran
     * out, a worker that was never started. Without it such a marker would suppress every later
     * remap of that class for good, with nothing to read anywhere: the silent no-op this whole
     * mechanism exists to prevent. Expiring it costs one superfluous remap at worst.
     */
    private const int PENDING_REMAP_SECONDS = 3600;

    /**
     * The delay a class save hands its own mapping update over with, so a worker picks the remap up
     * after the writing request has committed
     * ({@see \Pimcore\Bundle\GenericDataIndexBundle\EventSubscriber\ClassDefinitionIndexSubscriber}).
     */
    private const int DISPATCH_DELAY_MILLISECONDS = 2000;

    public function __construct(
        private MessageBusInterface $messageBus,
        private SettingsStoreResolverInterface $settingsStoreResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function hasPendingRemap(string $classDefinitionId): bool
    {
        $marker = $this->settingsStoreResolver->get($classDefinitionId, self::SETTINGS_STORE_SCOPE);

        if ($marker === null) {
            return false;
        }

        return (int) $marker->getData() + self::PENDING_REMAP_SECONDS > time();
    }

    /**
     * {@inheritdoc}
     */
    public function enqueueRemap(string $classDefinitionId): void
    {
        $this->messageBus->dispatch(
            new SearchIndexRemapMessage($classDefinitionId),
            [new DelayStamp(self::DISPATCH_DELAY_MILLISECONDS)]
        );

        // the hand-over time, so a marker the handler never got to stops answering by itself
        $this->settingsStoreResolver->set(
            $classDefinitionId,
            time(),
            self::SETTINGS_STORE_TYPE,
            self::SETTINGS_STORE_SCOPE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function releasePendingRemap(string $classDefinitionId): void
    {
        $this->settingsStoreResolver->delete($classDefinitionId, self::SETTINGS_STORE_SCOPE);
    }
}
