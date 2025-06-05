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

use Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class DocumentTypeService implements DocumentTypeServiceInterface
{
    public function __construct(
        private array $typeAdapters,
        private TypeAdapterLoaderInterface $typeAdapterLoader,
    ) {
    }


    // ToDo: Consider removing this method and using getTypeAdapter when document types from bundles are implemented
    public function tryTypeAdapter(string $documentType): ?SetterDataInterface
    {
        try {
            return $this->getTypeAdapter($documentType);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getTypeAdapter(string $documentType): SetterDataInterface
    {
        return $this->typeAdapterLoader->loadAdapter(
            $this->getAdapterClass($documentType)
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getAdapterClass(string $documentType): string
    {
        foreach ($this->typeAdapters as $adapter => $documentTypes) {
            if (in_array($documentType, $documentTypes, true)) {
                return $adapter;
            }
        }

        throw new InvalidArgumentException(sprintf('No adapter found for document type: %s', $documentType));
    }
}
