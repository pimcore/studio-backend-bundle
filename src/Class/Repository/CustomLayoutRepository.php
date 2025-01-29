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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Repository;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\CustomLayout\CustomLayoutResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutNewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutUpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Listing;

/**
 * @internal
 */
readonly class CustomLayoutRepository implements CustomLayoutRepositoryInterface
{
    public function __construct(
        private ClassDefinitionServiceResolverInterface $classDefinitionServiceResolver,
        private CustomLayoutResolverInterface $customLayoutResolver,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function getCustomLayouts(string $dataObjectClassId): array
    {
        $customLayoutListing = new Listing();
        $customLayoutListing->setFilter(
            fn (CustomLayout $layout) =>
                $layout->getClassId() === $dataObjectClassId &&
                !str_contains($layout->getId(), '.brick.')
        );

        return $customLayoutListing->load();
    }

    public function getCustomLayout(string $customLayoutId): CustomLayout
    {
        $cl = null;
        $exception = null;

        try {
            $cl = $this->customLayoutResolver->getById($customLayoutId);
        } catch (Exception $e) {
            $exception = $e;
        }
        if (!$cl || $exception) {
            throw new NotFoundException(
                'custom layout',
                $customLayoutId,
                'id',
                $exception
            );
        }

        return $cl;
    }

    public function deleteCustomLayout(string $customLayoutId): void
    {
        $this->getCustomLayout($customLayoutId)->delete();
    }

    public function createCustomLayout(
        string $customLayoutId,
        CustomLayoutNewParameters $customLayoutParameters
    ): CustomLayout
    {
        $customLayout = $this->customLayoutResolver->create(
            [
                'id' => $customLayoutId,
                'name' => $customLayoutParameters->getName(),
                'userOwner' => $this->securityService->getCurrentUser()->getId(),
                'classId' => $customLayoutParameters->getClassId(),
            ]
        );
        $customLayout->save();

        return $customLayout;
    }

    public function updateCustomLayout(
        string $customLayoutId,
        CustomLayoutUpdateParameters $customLayoutParameters
    ): CustomLayout
    {
        $customLayout = $this->getCustomLayout($customLayoutId);
        $config = $customLayoutParameters->getConfiguration();
        $values = $customLayoutParameters->getValues();

        $config['datatype'] = 'layout';
        $config['fieldtype'] = 'panel';
        $config['name'] = 'pimcore_root';

        $layout = $this->classDefinitionServiceResolver->generateLayoutTreeFromArray($config, true);
        $customLayout->setLayoutDefinitions($layout);
        $customLayout->setName($values['name']);
        $customLayout->setDescription($values['description'] ?? '');
        $customLayout->setDefault($values['default'] ?? false);
        $customLayout->save();

        return $customLayout;
    }
}
