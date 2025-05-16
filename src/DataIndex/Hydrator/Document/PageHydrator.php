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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\SearchResultItem\Page as Item;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Page;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\HydratorServiceInterface;

/**
 * @internal
 */
final readonly class PageHydrator implements PageHydratorInterface
{
    public function __construct(
        private HydratorServiceInterface $hydratorService,
    ) {
    }

    public function hydrate(Item $item): Page
    {
        return new Page(
            $item->getTitle(),
            $item->getDescription(),
            $item->getPrettyUrl(),
            ...$this->hydratorService->getBasePageSnippetData($item),
            ...$this->hydratorService->getBaseDocumentData($item)
        );
    }
}
