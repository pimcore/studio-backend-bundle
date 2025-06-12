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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\Document;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\DocumentSearchResultItem;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentDetail;
use  Pimcore\Bundle\StudioBackendBundle\Document\Service\HydratorServiceInterface;

/**
 * @internal
 */
final readonly class DocumentDetailHydrator implements DocumentDetailHydratorInterface
{
    public function __construct(
        private HydratorServiceInterface $hydratorService,
    ) {
    }

    public function hydrate(DocumentSearchResultItem $item): DocumentDetail
    {
        return new DocumentDetail(...$this->hydratorService->getBaseDocumentData($item));
    }
}
