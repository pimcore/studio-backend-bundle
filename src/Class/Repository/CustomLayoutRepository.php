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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Repository;

use Exception;
use JsonException;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\CustomLayout\CustomLayoutResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutNewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\JsonEncodingException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Listing;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;

/**
 * @internal
 */
readonly class CustomLayoutRepository implements CustomLayoutRepositoryInterface
{
    private const string NOT_WRITEABLE_EXCEPTION_MESSAGE = 'Custom Layout';

    public function __construct(
        private ClassDefinitionServiceResolverInterface $classDefinitionServiceResolver,
        private CustomLayoutResolverInterface $customLayoutResolver,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function getCustomLayoutsByClass(string $dataObjectClassId): array
    {
        $customLayoutListing = new Listing();
        $customLayoutListing->setFilter(
            fn (CustomLayout $layout) =>
                $layout->getClassId() === $dataObjectClassId &&
                !str_contains($layout->getId(), '.brick.')
        );

        return $customLayoutListing->load();
    }

    public function getAllCustomLayouts(): array
    {
        $customLayoutListing = new Listing();
        $customLayoutListing->setFilter(
            fn (CustomLayout $layout) => !str_contains($layout->getId(), '.brick.')
        );

        $customLayoutListing->setOrder(function (CustomLayout $a, CustomLayout $b) {
            return strcmp($a->getName(), $b->getName());
        });

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
                self::NOT_WRITEABLE_EXCEPTION_MESSAGE,
                $customLayoutId,
                'id',
                $exception
            );
        }

        return $cl;
    }

    public function deleteCustomLayout(CustomLayout $customLayout): void
    {
        try {
            $customLayout->delete();
        } catch (DefinitionWriteException) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        }
    }

    public function createCustomLayout(
        string $customLayoutId,
        CustomLayoutNewParameters $customLayoutParameters
    ): CustomLayout {
        try {
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
        } catch (DefinitionWriteException) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        }
    }

    public function updateCustomLayout(
        CustomLayout $customLayout,
        UpdateParameters $customLayoutParameters
    ): CustomLayout {
        try {
            $config = $customLayoutParameters->getConfiguration();
            $values = $customLayoutParameters->getValues();

            $config['datatype'] = 'layout';
            $config['fieldtype'] = 'panel';
            $config['name'] = 'pimcore_root';

            $layout = $this->classDefinitionServiceResolver->generateLayoutTreeFromArray(
                $config,
                true
            );
            $customLayout->setLayoutDefinitions($layout);
            $customLayout->setName($values['name']);
            $customLayout->setDescription($values['description'] ?? '');
            $customLayout->setDefault($values['default'] ?? false);
            $customLayout->save();

            return $customLayout;
        } catch (DefinitionWriteException) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    public function exportCustomLayoutAsJson(CustomLayout $customLayout): string
    {
        return $this->classDefinitionServiceResolver->generateCustomLayoutJson($customLayout);
    }

    public function importCustomLayoutFromJson(CustomLayout $customLayout, string $json): CustomLayout
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $layout = $this->classDefinitionServiceResolver->generateLayoutTreeFromArray(
                $data['layoutDefinitions'],
                true
            );
            $customLayout->setLayoutDefinitions($layout);
            $name = $data['name'] ?? '';
            if ($name !== '') {
                $customLayout->setName($name);
            }
            $customLayout->setDescription($data['description']);
            $customLayout->save();

            return $customLayout;
        } catch (DefinitionWriteException) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        } catch (JsonException $e) {
            throw new JsonEncodingException($e->getMessage());
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }
}
