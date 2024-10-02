<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter;

use Pimcore\Bundle\GenericDataIndexBundle\Exception\DocumentSearchException;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Document\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\DocumentHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;

/**
 * @internal
 */
final readonly class DocumentSearchAdapter implements DocumentSearchAdapterInterface
{

    public function __construct(
        private DocumentSearchServiceInterface $searchService,
        private DocumentHydratorInterface $hydratorService
    )
    {
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDocumentById(int $id): Document
    {
        try {
            $document = $this->searchService->byId($id);
        } catch (DocumentSearchException) {
            throw new SearchException(sprintf('Document with id %s', $id));
        }

        if (!$document) {
            throw new NotFoundException('Asset', $id);
        }

        return $this->hydratorService->hydrate($document);
    }

}