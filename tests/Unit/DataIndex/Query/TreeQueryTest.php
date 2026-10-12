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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\TreeQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\SettingsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\User;

/**
 * @internal
 */
final class TreeQueryTest extends Unit
{
    /**
     * Filters such as the tree PQL filter apply the permissions of the query's user, so the user has to be
     * set before the filters run.
     */
    public function testUserIsSetBeforeFiltersAreApplied(): void
    {
        $events = [];
        $user = new User();
        $query = $this->makeEmpty(DocumentQueryInterface::class, [
            'setUser' => function ($queryUser) use (&$events, $user, &$query) {
                $this->assertSame($user, $queryUser);
                $events[] = 'setUser';

                return $query;
            },
        ]);

        $filterService = $this->makeEmpty(SearchIndexFilterInterface::class, [
            'applyFilters' => function () use (&$events, $query) {
                $events[] = 'applyFilters';

                return $query;
            },
        ]);

        $treeQuery = new TreeQuery(
            $this->makeEmpty(QueryFactoryInterface::class, ['create' => $query]),
            $this->makeEmpty(SettingsServiceInterface::class),
            $this->makeEmpty(DataObjectServiceInterface::class),
            $this->makeEmpty(ElementServiceInterface::class),
        );

        $widget = new ElementTreeWidgetConfig(
            id: 'studio_document_tree_widget',
            name: 'Documents',
            contextPermissions: [],
            icon: new ElementIcon(ElementIconTypes::NAME->value, 'folder'),
            elementType: ElementTypes::TYPE_DOCUMENT,
            rootFolder: new RelatedElementData(1, ElementTypes::TYPE_DOCUMENT, ElementTypes::TYPE_FOLDER, '/'),
            pageSize: 30,
        );

        $treeQuery->get($widget, $filterService, $user, 1);

        $this->assertSame(['setUser', 'applyFilters'], $events);
    }
}
