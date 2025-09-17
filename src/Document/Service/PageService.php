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

use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\FrontendResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Listing;
use Pimcore\Model\Document\Page;
use function sprintf;
use function strlen;

/**
 * @internal
 */
final readonly class PageService implements PageServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private FrontendResolverInterface $frontendResolver,
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function checkPrettyUrl(string $prettyUrl, int $documentId): void
    {
        if (empty($prettyUrl)) {
            return;
        }

        $this->validatePrettyUrl($prettyUrl);

        $existingDocuments = $this->findConflictingDocuments($prettyUrl, $documentId);
        if (empty($existingDocuments)) {
            return;
        }

        $checkDocument = $this->getDocumentForValidation($documentId);
        $checkSiteId = $this->getSiteIdForDocument($checkDocument);

        $this->validateUrlUniquenessAcrossSites($existingDocuments, $checkSiteId);
    }

    /**
     * @throws ValidationFailedException
     */
    private function validatePrettyUrl(string $prettyUrl): void
    {
        $prettyUrl = rtrim($prettyUrl, '/');

        if (!str_starts_with($prettyUrl, '/')) {
            throw new ValidationFailedException('URL must start with /.');
        }

        if (strlen($prettyUrl) < 2) {
            throw new ValidationFailedException('URL must be at least 2 characters long.');
        }

        if (!$this->serviceResolver->isValidPath($prettyUrl, ElementTypes::TYPE_DOCUMENT)) {
            throw new ValidationFailedException('URL is invalid.');
        }
    }

    private function getExistingList(string $prettyUrl, int $documentId): Listing
    {
        $list = new Listing();
        $list->setCondition(
            '(CONCAT(`path`, `key`) = ? OR ' . '
            id IN (SELECT id from documents_page WHERE prettyUrl = ?)) ' .
            'AND id != ?',
            [$prettyUrl, $prettyUrl, $documentId]
        );

        return $list;
    }

    /**
     * @throws ValidationFailedException|NotFoundException
     */
    private function getDocumentForValidation(int $documentId): Page
    {
        $document = $this->getElement($this->serviceResolver, ElementTypes::TYPE_DOCUMENT, $documentId);

        if (!$document instanceof Page) {
            throw new ValidationFailedException(
                sprintf('Document with ID %d is not a Page.', $documentId)
            );
        }

        return $document;
    }

    private function findConflictingDocuments(string $prettyUrl, int $documentId): array
    {
        $list = $this->getExistingList($prettyUrl, $documentId);

        return $list->getTotalCount() > 0 ? $list->getDocuments() : [];
    }

    private function getSiteIdForDocument(Page $document): int
    {
        $site = $this->frontendResolver->getSiteForDocument($document);

        return $site?->getId() ?? 0;
    }

    private function validateUrlUniquenessAcrossSites(array $existingDocuments, int $checkSiteId): void
    {
        foreach ($existingDocuments as $document) {
            if ($this->isConflictingDocument($document, $checkSiteId)) {
                throw new ValidationFailedException('URL path already exists.');
            }
        }
    }

    private function isConflictingDocument(?Document $document, int $checkSiteId): bool
    {
        if (!$document instanceof Page) {
            return false;
        }

        $documentSiteId = $this->getSiteIdForDocument($document);

        return $documentSiteId === $checkSiteId;
    }
}
