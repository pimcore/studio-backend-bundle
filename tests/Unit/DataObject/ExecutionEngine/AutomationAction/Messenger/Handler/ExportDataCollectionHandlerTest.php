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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\ExecutionEngine\AutomationAction\Messenger\Handler;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Handler\ExportDataCollectionHandler;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Messages\ExportDataCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementContextServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\User;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class ExportDataCollectionHandlerTest extends Unit
{
    private const int ELEMENT_ID = 42;

    private const array GRID_VALUES = [self::ELEMENT_ID, '/cars/published,/cars/unpublished'];

    private bool $unpublishedElementsVisible = false;

    /**
     * @throws Exception
     */
    public function testCollectsGridValuesWithUnpublishedElementsVisible(): void
    {
        $user = new User();
        $columnCollection = new ColumnCollection([]);

        $gridService = $this->createMock(GridServiceInterface::class);
        $gridService->method('getConfigurationForExport')->willReturn($columnCollection);
        $gridService->expects($this->once())
            ->method('getGridValuesForElement')
            ->with($columnCollection, ElementTypes::TYPE_OBJECT, self::ELEMENT_ID, $user)
            ->willReturnCallback(function (): array {
                $this->assertTrue(
                    $this->unpublishedElementsVisible,
                    'Grid values must be collected with unpublished elements visible, not in the CLI context'
                );

                return self::GRID_VALUES;
            });

        $elementContextService = $this->createMock(ElementContextServiceInterface::class);
        $elementContextService->expects($this->never())->method('runInStudioContext');
        $elementContextService->expects($this->once())
            ->method('runWithUnpublishedElements')
            ->willReturnCallback(function (callable $callback): mixed {
                $this->unpublishedElementsVisible = true;
                $result = $callback();
                $this->unpublishedElementsVisible = false;

                return $result;
            });

        $jobRun = $this->createJobRun();
        $handler = $this->createHandler($gridService, $elementContextService, $jobRun, $user);

        $handler(new ExportDataCollectionMessage(1, 0));

        $this->assertSame(
            [self::ELEMENT_ID => self::GRID_VALUES],
            $jobRun->getContext()[StepConfig::GRID_EXPORT_DATA->value]
        );
    }

    private function createJobRun(): JobRun
    {
        $jobStep = new JobStep('export', ExportDataCollectionMessage::class, '', [
            StepConfig::ELEMENTS_TO_EXPORT->value => [['id' => self::ELEMENT_ID]],
            StepConfig::ELEMENT_CLASS_ID->value => 'CAR',
            StepConfig::CONFIG_COLUMNS->value => [],
        ]);
        $jobRun = new JobRun(1);
        $jobRun->setId(1);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        $jobRun->setJob(new Job('export', [$jobStep]));

        return $jobRun;
    }

    /**
     * @throws Exception
     */
    private function createHandler(
        GridServiceInterface $gridService,
        ElementContextServiceInterface $elementContextService,
        JobRun $jobRun,
        User $user
    ): ExportDataCollectionHandler {
        $handler = new ExportDataCollectionHandler(
            $this->makeEmpty(ColumnConfigurationServiceInterface::class),
            $this->makeEmpty(PublishServiceInterface::class),
            $this->makeEmpty(UserTopicServiceInterface::class, ['getUserTopic' => 'topic']),
            $this->makeEmpty(UserResolverInterface::class, ['getById' => $user]),
            $gridService,
            $elementContextService,
        );
        $handler->setJobRunExtractor($this->makeEmpty(JobRunExtractorInterface::class, [
            'getJobRun' => $jobRun,
            'getJobStep' => $jobRun->getJob()->getSteps()[0],
        ]));
        $handler->setJobRunRepository($this->makeEmpty(JobRunRepositoryInterface::class));
        $handler->setTranslator($this->makeEmpty(TranslatorInterface::class, [
            'trans' => static fn (string $id, array $parameters = []) => $id . ' ' . json_encode($parameters),
        ]));

        return $handler;
    }
}
