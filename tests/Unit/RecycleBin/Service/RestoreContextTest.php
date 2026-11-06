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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\RecycleBin\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RestoreContext;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class RestoreContextTest extends Unit
{
    public function testIsInactiveUntilARestoreStarts(): void
    {
        $this->assertNull((new RestoreContext())->getUser());
    }

    /**
     * @throws Exception
     */
    public function testKeepsTheLastSaveOfAnElement(): void
    {
        $placeholder = $this->makeEmpty(Concrete::class, ['getId' => 5]);
        $restoredObject = $this->makeEmpty(Concrete::class, ['getId' => 5]);
        $asset = $this->makeEmpty(Asset::class, ['getId' => 5]);

        $context = new RestoreContext();
        $context->start($this->makeEmpty(UserInterface::class));
        $context->addRelocatedElement($placeholder);
        $context->addRelocatedElement($asset);
        $context->addRelocatedElement($restoredObject);

        $this->assertSame([$restoredObject, $asset], $context->getRelocatedElements());
    }

    /**
     * @throws Exception
     */
    public function testClearEndsTheRestore(): void
    {
        $context = new RestoreContext();
        $context->start($this->makeEmpty(UserInterface::class));
        $context->addRelocatedElement($this->makeEmpty(Asset::class, ['getId' => 5]));

        $context->clear();

        $this->assertNull($context->getUser());
        $this->assertSame([], $context->getRelocatedElements());
    }

    /**
     * @throws Exception
     */
    public function testStartForgetsTheElementsOfAnEarlierRestore(): void
    {
        $user = $this->makeEmpty(UserInterface::class);
        $context = new RestoreContext();
        $context->start($this->makeEmpty(UserInterface::class));
        $context->addRelocatedElement($this->makeEmpty(Asset::class, ['getId' => 5]));

        $context->start($user);

        $this->assertSame($user, $context->getUser());
        $this->assertSame([], $context->getRelocatedElements());
    }
}
