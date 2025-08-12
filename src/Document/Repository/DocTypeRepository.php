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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Repository;

use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocTypeResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Model\Document\DocType;
use Pimcore\Model\Document\DocType\Listing;
use function sprintf;

/**
 * @internal
 */
final readonly class DocTypeRepository implements DocTypeRepositoryInterface
{
    public function __construct(
        private DocumentResolverInterface $documentResolver,
        private DocumentServiceResolverInterface $documentServiceResolver,
        private DocTypeResolverInterface $docTypeResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listDocTypes(?string $type = null): array
    {
        $listing = new Listing();
        if ($type !== null) {
            if (!$this->documentServiceResolver->isValidType($type)) {
                throw new InvalidArgumentException(sprintf('Invalid DocType type %s', $type));
            }

            $listing->setFilter(static function (DocType $docType) use ($type) {
                return $docType->getType() === $type;
            });
        }

        return $listing->getDocTypes();
    }

    /**
     * {@inheritdoc}
     */
    public function getById(string $id): DocType
    {
        $docType = $this->docTypeResolver->getById($id);
        if (!$docType instanceof DocType) {
            throw new NotFoundException(type: 'docType', id: $id);
        }

        if (!$docType->isWriteable()) {
            throw new NotWriteableException(type: 'DocType', message: 'DocType is not writeable');
        }

        return $docType;
    }

    /**
     * {@inheritdoc}
     */
    public function addDocType(): DocType
    {
        $docType = new DocType();
        if (!$docType->isWriteable()) {
            throw new NotWriteableException(type: 'DocType', message: 'DocType is not writeable');
        }

        return $docType;
    }

    public function getTypesConfiguration(): array
    {
        return $this->documentResolver->getTypesConfiguration();
    }
}
