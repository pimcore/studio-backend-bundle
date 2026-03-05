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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait;

use JsonException;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * @internal
 */
trait GroupInfoResolverTrait
{
    /**
     * @return array{0: ?string, 1: ?string} [groupName, groupDescription]
     */
    private function resolveGroupInfo(int $groupId, GroupRepositoryInterface $repository): array
    {
        try {
            $groupConfig = $repository->getById($groupId);

            return [$groupConfig->getName(), $groupConfig->getDescription()];
        } catch (NotFoundException) {
            return [null, null];
        }
    }

    private function resolveGroupName(int $groupId, GroupRepositoryInterface $repository): ?string
    {
        try {
            return $repository->getById($groupId)->getName();
        } catch (NotFoundException) {
            return null;
        }
    }

    private function getKeyDefinition(KeyConfig $keyConfig): ?array
    {
        $definition = $keyConfig->getDefinition();

        if ($definition === '') {
            return null;
        }

        try {
            return json_decode($definition, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }
}
