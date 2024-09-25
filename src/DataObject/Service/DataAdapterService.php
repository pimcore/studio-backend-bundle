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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use InvalidArgumentException;

/**
 * @internal
 */
final readonly class DataAdapterService implements DataAdapterServiceInterface
{
    public function __construct(
        private array $dataAdapters,
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
}
