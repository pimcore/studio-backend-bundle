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
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Hydrator\AssetVersionHydrator;
use Pimcore\Bundle\StudioBackendBundle\Version\Hydrator\CustomMetadataVersionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Service\VersionDetailServiceInterface;
use Pimcore\Model\Asset\Document;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class AssetVersionHydratorTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetHydratedVersionData(): void
    {
        $asset = new Document();
        $asset->setId(1);
        $asset->setMimeType('application/pdf');
        $asset->setFilename('test.pdf');

        $assetVersionHydrator = new AssetVersionHydrator(
            $this->makeEmpty(DocumentServiceInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(VersionDetailServiceInterface::class),
            $this->makeEmpty(CustomMetadataVersionHydratorInterface::class)
        );

        $hydrated = $assetVersionHydrator->hydrate($asset);
        $this->assertEquals('test.pdf', $hydrated->getFilename());
        $this->assertEmpty($hydrated->getAdditionalAttributes());
    }
}
