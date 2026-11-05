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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\CustomLayout\CustomLayoutResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\FieldCollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ObjectBrickRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportExecutor;
use Pimcore\Bundle\StudioBackendBundle\Class\Util\ClassDefinitionType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldCollectionDefinition;
use Pimcore\Model\UserInterface;
use function json_decode;
use function sprintf;

/**
 * The bulk import executor is the last line of defence for the import job: every item
 * is only imported if the job owner holds the permission of the item's own type.
 *
 * @internal
 */
final class BulkImportExecutorPermissionTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testItemIsRejectedWithoutPermissionOfItsType(): void
    {
        foreach (ClassDefinitionType::importOrder() as $type) {
            $user = $this->createMock(UserInterface::class);
            $user->method('isAllowed')->willReturnCallback(
                static fn (string $permission): bool => $permission !== $type->permission()
            );

            $classDefinitionRepository = $this->createMock(ClassDefinitionRepositoryInterface::class);
            $classDefinitionRepository->expects($this->never())->method('importFromJson');
            $fieldCollectionRepository = $this->createMock(FieldCollectionRepositoryInterface::class);
            $fieldCollectionRepository->expects($this->never())->method('importFromJson');
            $objectBrickRepository = $this->createMock(ObjectBrickRepositoryInterface::class);
            $objectBrickRepository->expects($this->never())->method('importFromJson');
            $customLayoutRepository = $this->createMock(CustomLayoutRepositoryInterface::class);
            $customLayoutRepository->expects($this->never())->method('importCustomLayoutFromJson');

            $executor = $this->createExecutor(
                $classDefinitionRepository,
                $fieldCollectionRepository,
                $objectBrickRepository,
                $customLayoutRepository
            );

            try {
                $executor->importSingleItem(
                    $type,
                    'Item',
                    ['key' => 'Item', 'name' => 'Item', 'className' => 'Item'],
                    $user
                );
                $this->fail(sprintf('Importing "%s" without its permission must be rejected.', $type->value));
            } catch (EnvironmentException $exception) {
                $this->assertStringContainsString(
                    sprintf('Access denied for importing %s "Item"', $type->value),
                    $exception->getMessage()
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testItemIsImportedWithPermissionOfItsType(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('isAllowed')->willReturnCallback(
            static fn (string $permission): bool => $permission === ClassDefinitionType::FieldCollection->permission()
        );

        $definition = new FieldCollectionDefinition();
        $fieldCollectionRepository = $this->createMock(FieldCollectionRepositoryInterface::class);
        $fieldCollectionRepository->method('getFieldCollectionByKey')->willReturn($definition);
        $fieldCollectionRepository->expects($this->once())
            ->method('importFromJson')
            ->with(
                $definition,
                $this->callback(static fn (string $json): bool => json_decode($json, true)['key'] === 'Item')
            )
            ->willReturn($definition);

        $executor = $this->createExecutor(
            $this->createMock(ClassDefinitionRepositoryInterface::class),
            $fieldCollectionRepository,
            $this->createMock(ObjectBrickRepositoryInterface::class),
            $this->createMock(CustomLayoutRepositoryInterface::class)
        );

        $executor->importSingleItem(
            ClassDefinitionType::FieldCollection,
            'Item',
            ['key' => 'Item'],
            $user
        );
    }

    private function createExecutor(
        ClassDefinitionRepositoryInterface $classDefinitionRepository,
        FieldCollectionRepositoryInterface $fieldCollectionRepository,
        ObjectBrickRepositoryInterface $objectBrickRepository,
        CustomLayoutRepositoryInterface $customLayoutRepository,
    ): BulkImportExecutor {
        return new BulkImportExecutor(
            $classDefinitionRepository,
            $this->createMock(ClassDefinitionResolverInterface::class),
            $fieldCollectionRepository,
            $objectBrickRepository,
            $customLayoutRepository,
            $this->createMock(CustomLayoutResolverInterface::class),
        );
    }
}
