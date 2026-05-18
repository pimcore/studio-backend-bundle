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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation\Listing;

/**
 * @internal
 */
final readonly class CollRelationRepository implements CollRelationRepositoryInterface
{
    public function __construct(
        private ListingFilterInterface $listingFilter,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getListing(FilterParameter $parameters, int $colId): Listing
    {
        $listing = new Listing();
        $listing->addConditionParam('`colId` = :colId', ['colId' => $colId]);

        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdate(int $colId, int $groupId, int $sorter): CollectionGroupRelation
    {
        $relation = new CollectionGroupRelation();
        $relation->setColId($colId);
        $relation->setGroupId($groupId);
        $relation->setSorter($sorter);

        try {
            $relation->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }

        return $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $colId, int $groupId): void
    {
        $relation = new CollectionGroupRelation();
        $relation->setColId($colId);
        $relation->setGroupId($groupId);
        $relation->delete();
    }
}
