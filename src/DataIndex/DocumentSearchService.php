<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter\DocumentSearchAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DocumentQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class DocumentSearchService implements DocumentSearchServiceInterface
{
    public function __construct(
        private DocumentSearchAdapterInterface $documentSearchAdapter,
        private DocumentQueryProviderInterface $documentQueryProvider,
    ) {
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDocumentById(int $id, ?UserInterface $user): Document
    {
        return $this->documentSearchAdapter->getDocumentById($id, $user);
    }

    public function getChildrenIds(string $parentPath, ?string $sortDirection = null): array
    {
        $query = $this->documentQueryProvider->createDocumentQuery();
        $query->filterPath($parentPath, true, false);
        if ($sortDirection) {
            $query->orderByPath($sortDirection);
        }

        return $this->fetchDocumentIds($query);
    }

    public function fetchDocumentIds(DocumentQueryInterface $documentQuery): array
    {
        return $this->documentSearchAdapter->fetchDocumentIds($documentQuery);
    }
}
