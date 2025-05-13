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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\DocumentSearchResultItem;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\DocumentHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\DocumentFolder;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Email;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Hardlink;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Link;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Page;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Snippet;
use Symfony\Contracts\Service\ServiceProviderInterface;
use function get_class;

/**
 * @internal
 */
final readonly class DocumentHydratorService implements DocumentHydratorServiceInterface
{
    public function __construct(
        private DocumentHydratorInterface $hydrator,
        private ServiceProviderInterface $hydratorLocator,
    ) {
    }

    public function hydrateDocuments(
        DocumentSearchResultItem $item
    ): Document|DocumentFolder|Email|Hardlink|Link|Page|Snippet {
        $class = get_class($item);
        if ($this->hydratorLocator->has($class)) {
            return $this->hydratorLocator->get($class)->hydrate($item);
        }

        return $this->hydrator->hydrate($item);
    }
}
