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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator\FolderPreviewSettingHydrator;

final class FolderPreviewSettingHydratorTest extends Unit
{
    public function testHydrateBuildsDtoWithImageSize(): void
    {
        $hydrator = new FolderPreviewSettingHydrator();

        $dto = $hydrator->hydrate('large');

        $this->assertSame('large', $dto->getImageSize());
    }
}
