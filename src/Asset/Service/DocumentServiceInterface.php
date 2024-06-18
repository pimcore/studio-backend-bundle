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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementProcessingNotCompletedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnprocessableContentException;
use Pimcore\Model\Asset\Document;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
interface DocumentServiceInterface
{
    /**
     * @throws ElementProcessingNotCompletedException
     * @throws ElementStreamResourceNotFoundException
     * @throws EnvironmentException
     * @throws UnprocessableContentException
     */
    public function getPreviewStream(Document $asset): StreamedResponse;

    public function isScanningEnabled(): bool;

    public function validatePdfScanStatus(
        Document $asset,
    ): void;
}
