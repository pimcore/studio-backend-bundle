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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\Document;

use Pimcore\Bundle\StaticResolverBundle\Lib\ConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ThumbnailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\StreamedResponseTrait;
use Pimcore\Config;
use Pimcore\Model\Asset\Document;
use Pimcore\Tool\Storage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class PdfScanService
{
    use StreamedResponseTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ThumbnailServiceInterface $thumbnailService,
        private Storage $storageTool,
        private ConfigResolverInterface $configResolver
    )
    {
    }

    public function isScanningEnabled(): bool
    {
        $assetsConfig = $this->configResolver->getSystemConfiguration('assets');
        return $assetsConfig['document']['scan_pdf'];
    }

    public function isDocumentSafe(
        Document $asset,
    ): bool
    {

    }
}
