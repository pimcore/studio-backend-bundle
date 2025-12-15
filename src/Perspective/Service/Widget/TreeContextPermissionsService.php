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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Widget;

use Pimcore\Bundle\StudioBackendBundle\Element\Model\ContextPermissionData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\Permissions\ContextPermissionsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ValidateElementTypeTrait;
use function array_key_exists;
use function is_bool;
use function sprintf;

/**
 * @internal
 */
final readonly class TreeContextPermissionsService implements TreeContextPermissionsServiceInterface
{
    use ValidateElementTypeTrait;

    public function __construct(
        private ContextPermissionsServiceInterface $contextPermissionService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function list(string $elementType, array $treeContextPermissions): array
    {
        $defaultPermissions = $this->contextPermissionService->list($elementType);
        if (empty($treeContextPermissions)) {
            return $defaultPermissions;
        }

        $merged = array_merge($defaultPermissions, $treeContextPermissions);
        ksort($merged);

        return $merged;
    }

    /**
     * {@inheritdoc}
     */
    public function update(ContextPermissionData $permissionData): array
    {
        $defaultPermissions = $this->contextPermissionService->list($permissionData->getElementType());
        $permission = $defaultPermissions[$permissionData->getKey()] ?? null;

        if ($permission === null) {
            throw new InvalidArgumentException(
                sprintf('Context permission with key "%s" does not exist', $permissionData->getKey())
            );
        }

        $defaultPermissions[$permissionData->getKey()] = $permissionData->getDefaultValue();

        return $defaultPermissions;
    }

    /**
     * {@inheritdoc}
     */
    public function updatePermissions(string $elementType, array $permissionData): array
    {
        $defaultPermissions = $this->contextPermissionService->list($elementType);
        if (empty($permissionData)) {
            return $defaultPermissions;
        }

        foreach ($permissionData as $key => $value) {
            if (!is_bool($value)) {
                continue;
            }

            if (array_key_exists($key, $defaultPermissions)) {
                $defaultPermissions[$key] = $value;
            }
        }

        return $defaultPermissions;
    }
}
