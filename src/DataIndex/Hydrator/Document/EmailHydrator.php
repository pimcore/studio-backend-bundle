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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\SearchResultItem\Email as Item;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Email;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\HydratorServiceInterface;

/**
 * @internal
 */
final readonly class EmailHydrator implements EmailHydratorInterface
{
    public function __construct(
        private HydratorServiceInterface $hydratorService,
    ) {
    }

    public function hydrate(Item $item): Email
    {
        return new Email(
            $item->getSubject(),
            $item->getFrom(),
            $item->getReplyTo(),
            $item->getTo(),
            $item->getCc(),
            $item->getBcc(),
            ...$this->hydratorService->getBasePageSnippetData($item),
            ...$this->hydratorService->getBaseDocumentData($item)
        );
    }
}
