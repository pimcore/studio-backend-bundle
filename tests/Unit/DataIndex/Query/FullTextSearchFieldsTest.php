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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Query;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\DocumentSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\FullTextSearch;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQuery;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\FullTextSearchFields;

/**
 * @internal
 */
final class FullTextSearchFieldsTest extends Unit
{
    public function testAssetFullTextFilterRestrictsSearchToExplicitFields(): void
    {
        $search = new AssetSearch();

        (new AssetQuery($search))->filterFullText('pro');

        $this->assertFullTextSearchUsesExplicitFields($search);
    }

    public function testDataObjectFullTextFilterRestrictsSearchToExplicitFields(): void
    {
        $search = new DataObjectSearch();
        $query = new DataObjectQuery($search, $this->makeEmpty(ClassDefinitionResolverInterface::class));

        $query->filterFullText('pro');

        $this->assertFullTextSearchUsesExplicitFields($search);
    }

    public function testDocumentFullTextFilterRestrictsSearchToExplicitFields(): void
    {
        $search = new DocumentSearch();

        (new DocumentQuery($search))->filterFullText('pro');

        $this->assertFullTextSearchUsesExplicitFields($search);
    }

    private function assertFullTextSearchUsesExplicitFields(SearchInterface $search): void
    {
        $modifiers = $search->getModifiers();
        $this->assertCount(1, $modifiers);

        $modifier = $modifiers[0];
        $this->assertInstanceOf(FullTextSearch::class, $modifier);
        $this->assertSame('pro', $modifier->getSearchTerm());
        $this->assertSame(FullTextSearchFields::FIELDS, $modifier->getFields());
    }
}
