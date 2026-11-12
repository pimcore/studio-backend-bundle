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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\LayoutEvent;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\ObjectLayoutHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Layout;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\LayoutService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\PreviewConfigServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LayoutServiceInterface as SecurityLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Text;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class LayoutServiceTest extends Unit
{
    private const string CLASS_ID = 'CAR';

    private const string TWIG_SOURCE = '{{ object.getKey() }}';

    /**
     * The class definition editor must receive the stored layout definition. Enriching it renders the
     * Twig of Text layout components (and resolves dynamic options), so saving the editor would
     * permanently replace the template with its rendered output.
     */
    public function testGetClassLayoutReturnsRawStoredDefinitionWithoutEnrichment(): void
    {
        $text = new Text();
        $text->setName('twigText');
        $text->setHtml(self::TWIG_SOURCE);

        $panel = new Panel();
        $panel->setName('pimcore_root');
        $panel->setChildren([$text]);

        $class = new ClassDefinition();
        $class->setLayoutDefinitions($panel);

        $classDefinitionRepository = $this->createMock(ClassDefinitionRepositoryInterface::class);
        $classDefinitionRepository
            ->expects($this->once())
            ->method('getClassDefinitionById')
            ->with(self::CLASS_ID)
            ->willReturn($class);

        $dataObjectServiceResolver = $this->createMock(DataObjectServiceResolverInterface::class);
        $dataObjectServiceResolver->expects($this->never())->method('enrichLayoutDefinition');

        $hydratedLayout = $this->createStub(Layout::class);
        $hydrator = $this->createMock(ObjectLayoutHydratorInterface::class);
        $hydrator
            ->expects($this->once())
            ->method('hydrateLayout')
            ->with($this->identicalTo($panel), null)
            ->willReturn($hydratedLayout);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(LayoutEvent::class), LayoutEvent::EVENT_NAME)
            ->willReturnArgument(0);

        $service = new LayoutService(
            $classDefinitionRepository,
            $this->createStub(CustomLayoutRepositoryInterface::class),
            $this->createStub(DataObjectServiceInterface::class),
            $dataObjectServiceResolver,
            $eventDispatcher,
            $hydrator,
            $this->createStub(PreviewConfigServiceInterface::class),
            $this->createStub(SecurityLayoutServiceInterface::class),
            $this->createStub(SecurityServiceInterface::class),
            $this->createStub(WorkflowDetailsServiceInterface::class),
        );

        $this->assertSame($hydratedLayout, $service->getClassLayout(self::CLASS_ID));
        $this->assertSame(self::TWIG_SOURCE, $text->getHtml());
    }
}
