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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTriggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The last moment a search index remap can still be handed over, and the one the write surfaces
 * cannot reach themselves.
 *
 * {@see SearchIndexRemapTriggerInterface::flush()} hands nothing over while the key writes it
 * answers for are inside a transaction, because coalescing against rows nobody else can read yet is
 * what loses a flag change. Every write surface in this bundle owns its transaction and has
 * committed it before it flushes, so for them that deferral never fires. It fires for a caller that
 * owns the transaction instead - and that caller is also the only one who could flush afterwards,
 * which is why this listener exists rather than a contract nobody reads.
 *
 * Terminate is after the response has been sent and after the command has returned, so the unit of
 * work is over and its transaction with it, whichever way it went. It is also before the services
 * are reset - the http kernel resets them at the next `boot()` and the console application between
 * consumed messages, neither of which is inside a terminate - which matters because the trigger's
 * `reset()` discards: a resetter that ran first would drop the notes this listener exists to hand
 * over, and drop them silently.
 *
 * The discard afterwards is not a second thought. A flush that deferred again - a caller that left
 * a transaction open past the end of the request - has no later moment to be made in, and a note
 * kept instead would be handed over by the next request of a worker-mode runtime, for a change that
 * request knows nothing about. A flush that handed its notes over has none left for it to drop.
 *
 * @internal
 */
final readonly class SearchIndexRemapFlushSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SearchIndexRemapTriggerInterface $searchIndexRemapTrigger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onTerminate',
            ConsoleEvents::TERMINATE => 'onTerminate',
        ];
    }

    public function onTerminate(): void
    {
        $this->searchIndexRemapTrigger->flush();
        $this->searchIndexRemapTrigger->discard();
    }
}
