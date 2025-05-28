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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Processor;

use OpenApi\Annotations\Operation;
use OpenApi\Annotations\PathItem;
use Pimcore\Bundle\StudioBackendBundle\Document\Attribute\Response\Content\OneOfDocumentsJson;
use Pimcore\Bundle\StudioBackendBundle\Document\Attribute\Response\Property\AnyOfDocument;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocumentTypeSchemasEvent;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function in_array;

/**
 * @internal
 */
final readonly class AddDocumentTypeSchemaProcessor
{
    private const array RELEVANT_PATHS = ['{prefix}/documents/tree', '{prefix}/documents/{id}'];

    private const array OPERATION_KEYS = ['get', 'put', 'patch'];

    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {

    }

    /**
     * @param PathItem[] $paths
     */
    public function __invoke(array $paths): void
    {
        foreach ($paths as $path) {
            if (!in_array($path->path, self::RELEVANT_PATHS, true)) {
                continue;
            }

            foreach (self::OPERATION_KEYS as $operationKey) {
                /** @var Operation|string $operation */
                $operation = $path->{$operationKey};

                if (!$operation instanceof Operation) {
                    continue;
                }

                $this->processOperation($operation);
            }
        }
    }

    private function processOperation(Operation $operation): void
    {
        foreach ($operation->responses as $response) {
            if (!$response instanceof SuccessResponse) {
                continue;
            }

            foreach ($response->content as $content) {
                match (true) {
                    $content->schema instanceof OneOfDocumentsJson => $this->processOneOfDocuments($content->schema),
                    $content->schema instanceof CollectionJson => $this->processCollection($content->schema),
                    default => null,
                };
            }
        }
    }

    private function processOneOfDocuments(OneOfDocumentsJson $schema): void
    {
        $schema->oneOf = $this->getCustomSchemas($schema->oneOf)->getDocumentTypeSchemas();
    }

    private function processCollection(CollectionJson $schema): void
    {
        foreach ($schema->properties as $property) {
            if (!$property instanceof AnyOfDocument) {
                continue;
            }

            $property->items->anyOf = $this->getCustomSchemas($property->items->anyOf)->getDocumentTypeSchemas();
        }
    }

    private function getCustomSchemas(array $schemas): DocumentTypeSchemasEvent
    {
        return $this->dispatcher->dispatch(
            new DocumentTypeSchemasEvent($schemas),
            DocumentTypeSchemasEvent::EVENT_NAME
        );
    }
}
