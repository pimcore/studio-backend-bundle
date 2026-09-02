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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ClassDefinitionService;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\User;

/**
 * @internal
 */
final class ClassDefinitionServiceTest extends Unit
{
    /**
     * enrichLayoutDefinition() cannot be given a Concrete $object here (the layout is
     * class-level, not object-level), but the resolved folder/object is still the correct
     * permission subject for "Viewable/Editable Languages" enforcement (PEES-1063). It must be
     * handed through context['object'] instead of being silently dropped.
     *
     * @see https://pimcore.atlassian.net/browse/PEES-1063
     */
    public function testGetFilteredLayoutDefinitionsPassesResolvedObjectViaContext(): void
    {
        $folderId = 42;
        $user = new User();
        $layout = $this->makeEmpty(Layout::class);
        $resolvedObject = $this->makeEmpty(Concrete::class);
        $capturedContext = null;

        $service = $this->createService(
            classDefinitionResolver: $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => new ClassDefinition(),
            ]),
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getCustomLayoutDefinitionForGridColumnConfig' => ['layoutDefinition' => $layout],
                'enrichLayoutDefinition' => function (
                    mixed $layoutArg,
                    mixed $objectArg = null,
                    array $context = [],
                    mixed $userArg = null
                ) use (&$capturedContext): void {
                    $capturedContext = $context;
                },
            ]),
            dataObjectResolver: $this->makeEmpty(DataObjectResolverInterface::class, [
                'getById' => $resolvedObject,
            ]),
        );

        $result = $service->getFilteredLayoutDefinitions('classId', $folderId, $user);

        self::assertSame($layout, $result);
        self::assertIsArray($capturedContext);
        self::assertSame($resolvedObject, $capturedContext['object']);
        self::assertSame('gridconfig', $capturedContext['purpose']);
    }

    /**
     * A folder id of 0 (no folder context) means there is no permission subject to resolve -
     * context['object'] must stay null rather than resolving object id 0.
     */
    public function testGetFilteredLayoutDefinitionsPassesNullObjectWhenFolderIdIsZero(): void
    {
        $layout = $this->makeEmpty(Layout::class);
        $capturedContext = null;

        $service = $this->createService(
            classDefinitionResolver: $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => new ClassDefinition(),
            ]),
            dataObjectServiceResolver: $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'getCustomLayoutDefinitionForGridColumnConfig' => ['layoutDefinition' => $layout],
                'enrichLayoutDefinition' => function (
                    mixed $layoutArg,
                    mixed $objectArg = null,
                    array $context = [],
                    mixed $userArg = null
                ) use (&$capturedContext): void {
                    $capturedContext = $context;
                },
            ]),
            dataObjectResolver: $this->makeEmpty(DataObjectResolverInterface::class),
        );

        $service->getFilteredLayoutDefinitions('classId', 0, new User());

        self::assertIsArray($capturedContext);
        self::assertNull($capturedContext['object']);
    }

    private function createService(
        ClassDefinitionResolverInterface $classDefinitionResolver,
        DataObjectServiceResolverInterface $dataObjectServiceResolver,
        DataObjectResolverInterface $dataObjectResolver,
    ): ClassDefinitionService {
        return new ClassDefinitionService(
            $classDefinitionResolver,
            $dataObjectServiceResolver,
            $dataObjectResolver,
        );
    }
}
