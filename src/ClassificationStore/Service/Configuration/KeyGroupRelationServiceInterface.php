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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface KeyGroupRelationServiceInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function listKeyGroupRelations(
        CollectionFilterParameter $parameters,
        int $groupId,
    ): Collection;

    /**
     * @throws ElementSavingFailedException
     */
    public function createOrUpdateKeyGroupRelation(
        KeyGroupRelationCreate $parameters
    ): KeyGroupRelationDetail;

    /**
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException core refused the sub group or the definition override
     * @throws NotFoundException the pair carries no assignment row
     */
    public function updateKeyGroupRelation(
        int $groupId,
        int $keyId,
        KeyGroupRelationUpdate $parameters
    ): KeyGroupRelationDetail;

    public function deleteKeyGroupRelation(int $keyId, int $groupId): void;
}
