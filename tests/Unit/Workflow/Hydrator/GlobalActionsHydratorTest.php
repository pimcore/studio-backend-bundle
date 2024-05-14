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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Workflow\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\GlobalActionsHydrator;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\GlobalAction;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowActionServiceInterface;
use Pimcore\Model\Asset;
use Pimcore\Workflow\Transition;

/**
 * @internal
 */
final class GlobalActionsHydratorTest extends Unit
{
    private GlobalActionsHydrator $hydrator;

    public function _before(): void
    {
        $this->hydrator = new GlobalActionsHydrator(
            $this->makeEmpty(WorkflowActionServiceInterface::class)

        );
    }

    public function testHydrateEmpty(): void
    {
        $this->assertEmpty($this->hydrator->hydrate([], new Asset()));
    }

    public function testHydrateWithAsset(): void
    {
        $transition = new Transition(
            'testTransition',
            'start',
            'end',
            ['objectLayout' => null]
        );

        $asset = new Asset();
        $hydratedTransitions = $this->hydrator->hydrate([$transition], $asset);
        $this->assertInstanceOf(GlobalAction::class, $hydratedTransitions[0]);
        $this->assertEquals($transition->getName(), $hydratedTransitions[0]->getName());
        $this->assertEquals($transition->getLabel(), $hydratedTransitions[0]->getLabel());
    }

    public function testHydrateWithNotes(): void
    {
        $transition = new Transition(
            'testObjectTransition',
            'start',
            'end',
            [
                'notes' => [
                    'commentEnabled' => true,
                    'myTestNote' => 'testNote',
                ],
                'objectLayout' => null,
            ]
        );

        $asset = new Asset();
        $hydratedTransitions = $this->hydrator->hydrate([$transition], $asset);
        $this->assertInstanceOf(GlobalAction::class, $hydratedTransitions[0]);
        $this->assertEquals($transition->getName(), $hydratedTransitions[0]->getName());
        $this->assertEquals($transition->getLabel(), $hydratedTransitions[0]->getLabel());
        $this->assertEquals('testNote', $hydratedTransitions[0]->getNotes()['myTestNote']);
    }
}
