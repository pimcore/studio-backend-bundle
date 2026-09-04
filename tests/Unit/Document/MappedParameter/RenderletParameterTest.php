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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Document\MappedParameter;

use ArgumentCountError;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter\RenderletParameter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
final class RenderletParameterTest extends Unit
{
    public function testParentDocumentIdIsRequired(): void
    {
        $this->expectException(ArgumentCountError::class);

        /** @phpstan-ignore argument.missing */
        new RenderletParameter(
            id: 5,
            type: ElementTypes::TYPE_DATA_OBJECT,
            controller: 'App\Controller\MyController::renderAction',
        );
    }

    public function testGetParentDocumentIdReturnsProvidedId(): void
    {
        $parameter = new RenderletParameter(
            id: 5,
            type: ElementTypes::TYPE_DATA_OBJECT,
            controller: 'App\Controller\MyController::renderAction',
            parentDocumentId: 38,
        );

        $this->assertSame(38, $parameter->getParentDocumentId());
    }
}
