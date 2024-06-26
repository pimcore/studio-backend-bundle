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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Workflow\Schema;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidActionTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;

/**
 * @internal
 */
final class SubmitActionTest extends Unit
{
    public function testSubmitActionException(): void
    {
        $this->expectException(InvalidActionTypeException::class);
        $this->expectExceptionMessage('Invalid workflow action type: someUnusualType');
        new SubmitAction(
            actionType: 'someUnusualType',
            elementId: 1,
            elementType: 'object',
            workflowName: 'myWorkflow',
            transition: 'myTransition',
            workflowOptions: []
        );
    }

    public function testSubmitActionElementException(): void
    {
        $this->expectException(InvalidElementTypeException::class);
        $this->expectExceptionMessage('Invalid element type: someUnusualElementType');
        new SubmitAction(
            actionType: 'global',
            elementId: 1,
            elementType: 'someUnusualElementType',
            workflowName: 'myWorkflow',
            transition: 'myTransition',
            workflowOptions: []
        );
    }

    public function testSubmitActionParameters(): void
    {
        $parameters = new SubmitAction(
            actionType: 'transition',
            elementId: 1,
            elementType: 'asset',
            workflowName: 'myWorkflow',
            transition: 'myTransition',
            workflowOptions: []
        );

        $this->assertEquals('transition', $parameters->getActionType());
        $this->assertEquals('asset', $parameters->getElementType());
    }
}
