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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider;

use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQueryInterface;

final readonly class DocumentQueryProvider implements DocumentQueryProviderInterface
{
    public function __construct(private SearchProviderInterface $searchProvider)
    {
    }

    public function createDocumentQuery(): DocumentQueryInterface
    {
        return new DocumentQuery($this->searchProvider->createDocumentSearch());
    }
}
