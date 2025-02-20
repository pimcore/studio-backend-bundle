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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Repository;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\TreeLevelData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Model\WidgetElementData;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface ElementTreeWidgetRepositoryInterface
{
    /**
     * @throws InvalidArgumentException|InvalidQueryTypeException
     * @throws InvalidFilterTypeException|NotFoundException
     */
    public function getWidgetByElement(
        array $allWidgets,
        string $elementType,
        int $elementId,
        SearchIndexFilterInterface $filterService,
        UserInterface $user
    ): ?WidgetElementData;

    /**
     * @throws ForbiddenException|InvalidArgumentException|InvalidFilterTypeException|InvalidQueryTypeException
     * @throws NotFoundException
     *
     * @return TreeLevelData[]
     */
    public function getTreeLevelData(
        WidgetElementData $widgetElementData,
        SearchIndexFilterInterface $filterService,
        UserInterface $user
    ): array;
}
