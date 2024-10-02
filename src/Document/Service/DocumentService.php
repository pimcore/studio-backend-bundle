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


namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;


use Pimcore\Bundle\StudioBackendBundle\DataIndex\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocumentEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class DocumentService implements DocumentServiceInterface
{


    public function __construct(
        private DocumentSearchServiceInterface $documentSearchService,
        private EventDispatcherInterface $eventDispatcher,
    )
    {
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDocument(int $id): Document
    {
        $document = $this->documentSearchService->getDocumentById($id);

        $this->eventDispatcher->dispatch(
            new DocumentEvent($document),
            DocumentEvent::EVENT_NAME
        );

        return $document;
    }

}