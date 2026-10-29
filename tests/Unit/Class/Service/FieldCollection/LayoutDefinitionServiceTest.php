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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Service\FieldCollection;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\FieldCollection\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ConfigLayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldCollection\LayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\FieldCollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\LayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection\LayoutDefinitionService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ActiveGroupTitleEnricherInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class LayoutDefinitionServiceTest extends Unit
{
    private const int OBJECT_ID = 5;

    private const string CLASS_ID = 'CAR';

    private const string COLLECTION_KEY = 'technical';

    private const string CALL_ENRICH = 'enrich';

    private const string CALL_TITLES = 'titles';

    private const string CALL_HYDRATE = 'hydrate';

    /**
     * A classification store field nested in a field collection is reached through this endpoint only,
     * so its already-active group and key titles have to be filled here as well.
     */
    public function testGetLayoutDefinitionsForObjectFillsClassificationStoreTitlesBeforeHydration(): void
    {
        $panel = new Panel();
        $panel->setName('pimcore_root');

        $definition = $this->createMock(Definition::class);
        $definition->method('getLayoutDefinitions')->willReturn($panel);

        $calls = [];

        $dataObjectServiceResolver = $this->createMock(DataObjectServiceResolverInterface::class);
        $dataObjectServiceResolver
            ->expects($this->once())
            ->method('enrichLayoutDefinition')
            ->willReturnCallback(function (mixed $layout) use (&$calls, $panel): void {
                $this->assertSame($panel, $layout);
                $calls[] = self::CALL_ENRICH;
            });

        $activeGroupTitleEnricher = $this->createMock(ActiveGroupTitleEnricherInterface::class);
        $activeGroupTitleEnricher
            ->expects($this->once())
            ->method('enrich')
            ->with($this->identicalTo($panel))
            ->willReturnCallback(static function () use (&$calls): void {
                $calls[] = self::CALL_TITLES;
            });

        $hydratedDefinition = new LayoutDefinition(self::COLLECTION_KEY, 'layout');
        $layoutDefinitionHydrator = $this->createMock(LayoutDefinitionHydratorInterface::class);
        $layoutDefinitionHydrator
            ->expects($this->once())
            ->method('hydrate')
            ->with($this->identicalTo($definition))
            ->willReturnCallback(static function () use (&$calls, $hydratedDefinition): LayoutDefinition {
                $calls[] = self::CALL_HYDRATE;

                return $hydratedDefinition;
            });

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);

        $service = new LayoutDefinitionService(
            $this->createDataObjectResolver(),
            $dataObjectServiceResolver,
            $this->createClassDefinitionResolver(),
            $this->createDefinitionResolver($definition),
            $this->createStub(FieldCollectionRepositoryInterface::class),
            $layoutDefinitionHydrator,
            $this->createStub(ConfigLayoutDefinitionHydratorInterface::class),
            $eventDispatcher,
            $activeGroupTitleEnricher,
        );

        $this->assertSame([$hydratedDefinition], $service->getLayoutDefinitionsForObject(self::OBJECT_ID));
        $this->assertSame([self::CALL_ENRICH, self::CALL_TITLES, self::CALL_HYDRATE], $calls);
    }

    private function createDataObjectResolver(): DataObjectResolverInterface
    {
        $dataObject = $this->createMock(Concrete::class);
        $dataObject->method('getClassId')->willReturn(self::CLASS_ID);

        $dataObjectResolver = $this->createMock(DataObjectResolverInterface::class);
        $dataObjectResolver
            ->method('getById')
            ->with(self::OBJECT_ID)
            ->willReturn($dataObject);

        return $dataObjectResolver;
    }

    private function createClassDefinitionResolver(): ClassDefinitionResolverInterface
    {
        // setAllowedTypes() loads the definition from disk, which a unit test has no checkout for
        $fieldCollections = $this->createMock(Fieldcollections::class);
        $fieldCollections->method('getAllowedTypes')->willReturn([self::COLLECTION_KEY]);

        $classDefinition = new ClassDefinition();
        $classDefinition->setFieldDefinitions(['technicalData' => $fieldCollections]);

        $classDefinitionResolver = $this->createMock(ClassDefinitionResolverInterface::class);
        $classDefinitionResolver
            ->method('getById')
            ->with(self::CLASS_ID)
            ->willReturn($classDefinition);

        return $classDefinitionResolver;
    }

    private function createDefinitionResolver(Definition $definition): DefinitionResolverInterface
    {
        $definitionResolver = $this->createMock(DefinitionResolverInterface::class);
        $definitionResolver
            ->method('getByKey')
            ->with(self::COLLECTION_KEY)
            ->willReturn($definition);

        return $definitionResolver;
    }
}
