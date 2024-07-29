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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementProcessingNotCompletedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnprocessableContentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\StreamedResponseTrait;
use Pimcore\Document\Adapter;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Enum\PdfScanStatus;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function sprintf;

/**
 * @internal
 */
final readonly class DocumentService implements DocumentServiceInterface
{
    use StreamedResponseTrait;

    public function __construct(
        private ConfigResolverInterface $configResolver,
        private DocumentResolverInterface $documentResolver,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws ElementProcessingNotCompletedException
     * @throws ElementStreamResourceNotFoundException
     * @throws EnvironmentException
     * @throws UnprocessableContentException
     */
    public function getPreviewStream(Document $asset): StreamedResponse
    {
        if ($asset->getMimeType() !== MimeTypes::PDF->value) {
            return $this->getStreamFromDocument($asset);
        }

        if ($this->isScanningEnabled()) {
            $this->validatePdfScanStatus($asset);
            if ($asset->getScanStatus() === null) {
                $this->eventDispatcher->dispatch(
                    new AssetUpdateTasksMessage($asset->getId())
                );

                throw new ElementProcessingNotCompletedException($asset->getId());
            }
        }

        return $this->getStreamedResponse(
            element: $asset,
            contentDisposition: HttpResponseHeaders::INLINE_TYPE->value
        );
    }

    public function isScanningEnabled(): bool
    {
        $assetsConfig = $this->configResolver->getSystemConfiguration('assets');

        return $assetsConfig['document']['scan_pdf'];
    }

    /**
     * @throws ElementProcessingNotCompletedException|UnprocessableContentException
     */
    public function validatePdfScanStatus(
        Document $asset,
    ): void {
        $scanStatus = $asset->getScanStatus();

        match (true) {
            $scanStatus === PdfScanStatus::UNSAFE =>
                throw new UnprocessableContentException('Pdf is not safe for preview'),
            $scanStatus === PdfScanStatus::IN_PROGRESS =>
                throw new ElementProcessingNotCompletedException($asset->getId()),
            default => null
        };
    }

    /**
     * @throws EnvironmentException|ElementStreamResourceNotFoundException
     */
    private function getStreamFromDocument(Document $asset): StreamedResponse
    {
        $adapter = $this->documentResolver->getDefaultAdapter();
        if (!$adapter) {
            throw new EnvironmentException('Document adapter is not available.');
        }

        $this->validateAssetDocument($asset);

        return $this->getPdfStreamFromAdapter($adapter, $asset);
    }

    /**
     * @throws EnvironmentException|ElementStreamResourceNotFoundException
     */
    private function validateAssetDocument(Document $asset): void
    {
        $fileName = $asset->getFilename();
        if (!$this->documentResolver->isFileTypeSupported($fileName)) {
            throw new EnvironmentException(
                sprintf(
                    'Document adapter does not support the file type for document %s with ID %s.',
                    $fileName,
                    $asset->getId()
                )
            );
        }

        if (!$asset->getPageCount()) {
            throw new ElementStreamResourceNotFoundException(
                id: $asset->getId(),
                type: sprintf(
                    '%s with mimetype(%s) and',
                    $asset->getType(),
                    $asset->getMimeType()
                )
            );
        }
    }

    /**
     * @throws ElementStreamResourceNotFoundException
     */
    private function getPdfStreamFromAdapter(
        Adapter $adapter,
        Document $asset
    ): StreamedResponse {
        try {
            $stream = $adapter->getPdf($asset);

            return new StreamedResponse(
                function () use ($stream) {
                    fpassthru($stream);
                },
                HttpResponseCodes::SUCCESS->value,
                [
                    HttpResponseHeaders::HEADER_CONTENT_TYPE->value => MimeTypes::PDF->value,
                    HttpResponseHeaders::HEADER_CONTENT_DISPOSITION->value => sprintf(
                        '%s; filename="%s"',
                        HttpResponseHeaders::INLINE_TYPE->value,
                        $asset->getFileSize()
                    ),
                ]
            );
        } catch (Exception) {
            throw new ElementStreamResourceNotFoundException(
                id: $asset->getId(),
                type: sprintf(
                    '%s with mimetype(%s) and',
                    $asset->getType(),
                    $asset->getMimeType()
                )
            );
        }
    }
}
