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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Util;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedColumnSourceFieldContext;

/**
 * @internal
 */
final class AdvancedColumnSourceFieldContextTest extends Unit
{
    public function testDefaultsToNotResolvingASourceField(): void
    {
        $context = new AdvancedColumnSourceFieldContext();

        self::assertFalse($context->isResolvingSourceField());
    }

    public function testTracksTheFlagItWasLastSetTo(): void
    {
        $context = new AdvancedColumnSourceFieldContext();

        $context->setResolvingSourceField(true);
        self::assertTrue($context->isResolvingSourceField());

        $context->setResolvingSourceField(false);
        self::assertFalse($context->isResolvingSourceField());
    }
}
