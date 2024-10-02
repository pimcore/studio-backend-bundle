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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter\DocumentSearchAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;

/**
 * @internal
 */
final readonly class DocumentSearchService implements DocumentSearchServiceInterface
{
    public function __construct(
        private DocumentSearchAdapterInterface $documentSearchAdapter
    )
    {
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDocumentById(int $id): Document
    {
        return $this->documentSearchAdapter->getDocumentById($id);
    }

}