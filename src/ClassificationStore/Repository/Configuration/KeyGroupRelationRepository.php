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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing;
use Pimcore\Model\Exception\InvalidConfigException;

/**
 * @internal
 */
final readonly class KeyGroupRelationRepository implements KeyGroupRelationRepositoryInterface
{
    public function __construct(
        private ListingFilterInterface $listingFilter,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getListingByGroupId(FilterParameter $parameters, int $groupId): Listing
    {
        $listing = new Listing();
        $listing->addConditionParam('`groupId` = :groupId', ['groupId' => $groupId]);

        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdate(int $keyId, int $groupId, int $sorter, bool $mandatory): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId($groupId);
        $relation->setSorter($sorter);
        $relation->setMandatory($mandatory);

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
    public function update(int $keyId, int $groupId, KeyGroupRelationUpdate $parameters): KeyGroupRelation
    {
        $relation = $this->getByKeyAndGroup($keyId, $groupId);

        try {
            $this->applyUpdate($relation, $parameters);
            $relation->save();
        } catch (Exception $e) {
            throw $this->mapWriteFailure($e);
        }

        return $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $keyId, int $groupId): void
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId($groupId);
        $relation->delete();
    }

    /**
     * A refusal by the classification store is the fault of the request, so it surfaces as 422 with the
     * message of the refusal rather than as the 500 a save failure produces.
     */
    private function mapWriteFailure(Exception $e): AbstractApiException
    {
        if ($e instanceof InvalidConfigException) {
            return new InvalidArgumentException($e->getMessage(), $e);
        }

        return new ElementSavingFailedException(null, $e->getMessage(), $e);
    }

    /**
     * @throws InvalidConfigException
     */
    private function applyUpdate(KeyGroupRelation $relation, KeyGroupRelationUpdate $parameters): void
    {
        if ($parameters->hasSorter()) {
            $relation->setSorter($parameters->getSorter());
        }

        if ($parameters->hasMandatory()) {
            $relation->setMandatory($parameters->isMandatory());
        }

        if ($parameters->hasDefinitionOverride()) {
            $relation->setDefinitionOverride($parameters->getDefinitionOverride());
        }

        if ($parameters->hasSubGroup()) {
            $relation->setSubGroup($parameters->getSubGroup());
        }

        if ($parameters->hasMetadata()) {
            $relation->setMetadata($parameters->getMetadata());
        }
    }

    /**
     * The row the update writes is loaded here rather than taken from the request memo of the key
     * relation resolver, which hands out clones of what it saw earlier in the request.
     *
     * @throws NotFoundException
     */
    private function getByKeyAndGroup(int $keyId, int $groupId): KeyGroupRelation
    {
        $listing = new Listing();
        $listing->addConditionParam('`keyId` = :keyId', ['keyId' => $keyId]);
        $listing->addConditionParam('`groupId` = :groupId', ['groupId' => $groupId]);

        $relations = $listing->load();

        if ($relations === []) {
            throw new NotFoundException('KeyGroupRelation', $keyId);
        }

        return $relations[0];
    }
}
