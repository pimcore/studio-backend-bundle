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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;

/**
 * @internal
 */
final readonly class ElementContextService implements ElementContextServiceInterface
{
    public function __construct(
        private DataObjectResolverInterface $dataObjectResolver,
        private DocumentResolverInterface $documentResolver,
        private LocalizedFieldResolverInterface $localizedFieldResolver,
    ) {
    }

    public function runInStudioContext(callable $callback): mixed
    {
        $fallbackValues = $this->localizedFieldResolver->getGetFallbackValues();
        $this->localizedFieldResolver->setGetFallbackValues(false);

        try {
            return $this->runWithUnpublishedElements($callback);
        } finally {
            $this->localizedFieldResolver->setGetFallbackValues($fallbackValues);
        }
    }

    public function runWithUnpublishedElements(callable $callback): mixed
    {
        $hideUnpublishedObjects = $this->dataObjectResolver->doHideUnpublished();
        $hideUnpublishedDocuments = $this->documentResolver->doHideUnpublished();

        $this->dataObjectResolver->setHideUnpublished(false);
        $this->documentResolver->setHideUnpublished(false);

        try {
            return $callback();
        } finally {
            $this->dataObjectResolver->setHideUnpublished($hideUnpublishedObjects);
            $this->documentResolver->setHideUnpublished($hideUnpublishedDocuments);
        }
    }
}
