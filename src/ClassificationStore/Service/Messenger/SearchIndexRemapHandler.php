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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Messenger;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Message\UpdateClassMappingMessage;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapQueueInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Turns one queued remap into the mapping update a class save hands over, so that the mapping is
 * rewritten and the elements are re-indexed by the index worker rather than by the request that
 * wrote the key.
 *
 * @internal
 */
#[AsMessageHandler]
final readonly class SearchIndexRemapHandler
{
    public function __construct(
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private MessageBusInterface $messageBus,
        private SearchIndexRemapQueueInterface $remapQueue,
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(SearchIndexRemapMessage $message): void
    {
        $classDefinitionId = $message->getClassDefinitionId();

        // released first: a flag change arriving while this remap is being handed on is a change
        // this remap has not read, so it has to queue its own rather than be taken for a duplicate
        $this->remapQueue->releasePendingRemap($classDefinitionId);

        $classDefinition = $this->classDefinitionResolver->getById($classDefinitionId, true);

        if ($classDefinition === null) {
            return;
        }

        $this->messageBus->dispatch(
            new UpdateClassMappingMessage(
                classDefinition: $classDefinition,
                dispatchQueueMessages: true
            )
        );
    }
}
