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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ExecutionEngine\Util\Trait;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use function array_merge;

/**
 * @internal
 */
final class HandlerProgressTraitTest extends Unit
{
    private const string PROCESSED_ELEMENTS = 'processedElements';

    private const string ELEMENTS_PER_STEP = 'elementsPerStep';

    private const string CURRENT_STEP = 'currentStep';

    public function testResetDiscardsRedeliveredCounter(): void
    {
        $jobRun = $this->createJobRun(2, [
            self::PROCESSED_ELEMENTS => 130,
            self::CURRENT_STEP => 2,
            self::ELEMENTS_PER_STEP => 500,
        ]);

        $this->createTraitHelper()->callResetStepProgress($jobRun);

        $context = $jobRun->getContext();
        $this->assertSame(0, $context[self::PROCESSED_ELEMENTS]);
        $this->assertSame(2, $context[self::CURRENT_STEP]);
        $this->assertNull($context[self::ELEMENTS_PER_STEP]);
    }

    public function testResetOnFreshRun(): void
    {
        $jobRun = $this->createJobRun(0, null);

        $this->createTraitHelper()->callResetStepProgress($jobRun);

        $context = $jobRun->getContext();
        $this->assertSame(0, $context[self::PROCESSED_ELEMENTS]);
        $this->assertSame(0, $context[self::CURRENT_STEP]);
        $this->assertNull($context[self::ELEMENTS_PER_STEP]);
    }

    public function testResetOnStepTransition(): void
    {
        $jobRun = $this->createJobRun(2, [
            self::PROCESSED_ELEMENTS => 300,
            self::CURRENT_STEP => 1,
            self::ELEMENTS_PER_STEP => 500,
        ]);

        $this->createTraitHelper()->callResetStepProgress($jobRun);

        $context = $jobRun->getContext();
        $this->assertSame(0, $context[self::PROCESSED_ELEMENTS]);
        $this->assertSame(2, $context[self::CURRENT_STEP]);
        $this->assertNull($context[self::ELEMENTS_PER_STEP]);
    }

    private function createJobRun(int $currentStep, ?array $context): JobRun
    {
        $jobRun = new JobRun(1);
        $jobRun->setId(42);
        $jobRun->setCurrentStep($currentStep);
        $jobRun->setContext($context);

        return $jobRun;
    }

    private function createTraitHelper(): object
    {
        return new class {
            use HandlerProgressTrait;

            // mirrors AbstractHandler::updateJobRunContextValues without persisting
            protected function updateJobRunContextValues(
                JobRun $jobRun,
                array $values,
                bool $persist = true
            ): void {
                $jobRun->setContext(
                    array_merge(
                        $jobRun->getContext() ?? [],
                        $values
                    )
                );
            }
        };
    }
}
