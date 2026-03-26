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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class DataAdapterService implements DataAdapterServiceInterface
{
    public function __construct(
        private array $dataAdapters,
        private DataAdapterLoaderInterface $dataAdapterLoader,
    ) {
    }

    public function getAdaptersMapping(): array
    {
        return $this->dataAdapters;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getFieldDefinitionAdapterClass(string $fieldDefinitionType): string
    {
        $adapterMapping = $this->getAdaptersMapping();
        foreach ($adapterMapping as $adapter => $fieldTypes) {
            if (in_array($fieldDefinitionType, $fieldTypes, true)) {
                return $adapter;
            }
        }

        throw new InvalidArgumentException(
            sprintf('No adapter found for field definition of type "%s"', $fieldDefinitionType)
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getDataAdapter(string $fieldDefinitionType): SetterDataInterface
    {
        return $this->dataAdapterLoader->loadAdapter(
            $this->getFieldDefinitionAdapterClass($fieldDefinitionType)
        );
    }

    public function tryDataAdapter(string $fieldDefinitionType): ?SetterDataInterface
    {
        try {
            return $this->getDataAdapter($fieldDefinitionType);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
