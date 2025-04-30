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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\StaticResolverBundle\Lib\ConfigResolverInterface as SystemConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StreamedResponseTrait;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function sprintf;

/**
 * @internal
 */
final readonly class BinaryService implements BinaryServiceInterface
{
    use StreamedResponseTrait;

    private const string PAGE_PREVIEW_NAME = 'document-page-screenshot-%d@2x.jpeg';

    private const string PAGE_PREVIEW_LOCATION = '/document-page-previews/' . self::PAGE_PREVIEW_NAME;

    public function __construct(
        private SystemConfigResolverInterface $systemConfigResolver,
        private StorageServiceInterface $storageService
    ) {
    }

    /**
     * @throws EnvironmentException|InvalidElementTypeException|StreamResourceNotFoundException
     */
    public function streamPagePreviewImage(Document $document): StreamedResponse
    {
        if (!$document instanceof Page) {
            throw new InvalidElementTypeException($document->getType(), ElementTypes::TYPE_DOCUMENT);
        }

        $path = $this->getPagePreviewPath($document);
        if ($path === null) {
            throw new StreamResourceNotFoundException(
                sprintf('Could not find preview for page with ID: %d', $document->getId())
            );
        }

        return $this->getFileStreamedResponse(
            $path,
            MimeTypes::JPEG->value,
            sprintf(self::PAGE_PREVIEW_NAME, $document->getId()),
            $this->storageService->getTempStorage(),
            HttpResponseHeaders::INLINE_TYPE->value
        );
    }

    /**
     * @throws EnvironmentException
     */
    public function getPagePreviewPath(Page $page): ?string
    {
        if (!$this->isPagePreviewActive()) {
            return null;
        }

        $path = sprintf(self::PAGE_PREVIEW_LOCATION, $page->getId());

        if (!$this->storageService->tempFileExists($path)) {
            return null;
        }

        return $path;
    }

    /**
     * @throws EnvironmentException
     */
    public function hasPagePreview(Page $page): bool
    {
        return $this->getPagePreviewPath($page) !== null;
    }

    private function isPagePreviewActive(): bool
    {
        $documentConfig = $this->systemConfigResolver->getSystemConfiguration('documents');

        return isset($documentConfig['generate_preview']) && $documentConfig['generate_preview'];
    }
}
