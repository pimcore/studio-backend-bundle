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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Workflow\Schema;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidActionTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;

/**
 * @internal
 */
#[CoversClass(SubmitAction::class)]
#[UsesClass(AbstractApiException::class)]
#[UsesClass(InvalidActionTypeException::class)]
#[UsesClass(InvalidElementTypeException::class)]
final class SubmitActionTest extends TestCase
{
    public function testSubmitActionException(): void
    {
        $this->expectException(InvalidActionTypeException::class);
        $this->expectExceptionMessage('Invalid workflow action type: someUnusualType');
        new SubmitAction(
            actionType: 'someUnusualType',
            elementId: 1,
            elementType: 'object',
            workflowId: 'myWorkflow',
            transitionId: 'myTransition',
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
            workflowId: 'myWorkflow',
            transitionId: 'myTransition',
            workflowOptions: []
        );
    }

    public function testSubmitActionParameters(): void
    {
        $parameters = new SubmitAction(
            actionType: 'transition',
            elementId: 1,
            elementType: 'asset',
            workflowId: 'myWorkflow',
            transitionId: 'myTransition',
            workflowOptions: []
        );

        $this->assertEquals('transition', $parameters->getActionType());
        $this->assertEquals('asset', $parameters->getElementType());
    }
}
