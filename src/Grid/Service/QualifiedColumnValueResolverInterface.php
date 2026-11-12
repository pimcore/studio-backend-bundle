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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ClassificationStoreConfig;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

/**
 * What a Classification Store grid column shows, and exports, for a key that declares qualifiers
 * (a `multiValue` key): the one variant its column addresses.
 *
 * @internal
 */
interface QualifiedColumnValueResolverInterface
{
    /**
     * The cell of a column: `$value` as it is where the key declares no qualifiers (or is unknown),
     * otherwise the one value of the column's variant, or for a repeating key the entry list the
     * write path reads, so that a cell sent back unchanged keeps every position.
     */
    public function getCellValue(mixed $value, ClassificationStoreConfig $config): mixed;

    /**
     * Whether the column's key declares qualifiers.
     *
     * @throws InvalidArgumentException when no key carries the column's key id
     */
    public function declaresQualifiers(ClassificationStoreConfig $config): bool;

    /**
     * The export of a column of a key that declares qualifiers: the values its variant holds, each
     * formatted by the key's own field definition. A repeating key exports them as a bare JSON array.
     *
     * @param array<string, mixed> $legacyParameters
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getExportValue(
        Concrete $element,
        string $fieldName,
        ClassificationStoreConfig $config,
        string $locale,
        Data $fieldDefinition,
        array $legacyParameters
    ): mixed;
}
