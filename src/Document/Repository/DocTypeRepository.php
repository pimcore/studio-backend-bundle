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

use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\Document\DocType;
use Pimcore\Model\Document\DocType\Listing;

/**
 * @internal
 */
final readonly class DocTypeRepository implements DocTypeRepositoryInterface
{
    public function __construct(
        private DocumentServiceResolverInterface $documentServiceResolver,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function listDocTypes(string $type = null): array
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
}
