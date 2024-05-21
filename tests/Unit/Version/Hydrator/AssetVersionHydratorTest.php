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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Version\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementProcessingNotCompletedException;
use Pimcore\Bundle\StudioBackendBundle\Version\Hydrator\AssetVersionHydrator;
use Pimcore\Model\Asset\Document;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class AssetVersionHydratorTest extends Unit
{
    public function testGetHydratedVersionData(): void
    {
        $asset = new Document();
        $asset->setId(1);
        $asset->setMimeType('application/pdf');
        $asset->setFilename('test.pdf');

        $assetVersionHydrator = new AssetVersionHydrator(
            $this->makeEmpty(EventDispatcherInterface::class)
        );

        // Status is not set properly
        $this->expectException(ElementProcessingNotCompletedException::class);
        $this->expectExceptionMessage('Element with ID 1 was not processed yet');
        $assetVersionHydrator->hydrate($asset);
    }
}
