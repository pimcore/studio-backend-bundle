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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * The one place a key's qualified-value declaration is read, so that the layout paths and the
 * object data path cannot drift apart on what a key permits.
 *
 * The declaration is the key's type: a key declares qualifiers when its field definition, resolved
 * from its type and its `definition` blob, is a core `multiValue` definition. `variants` and
 * `multiValue` written into the blob of a key of any other type declare nothing. Nothing is
 * resolved against the engine of the key's store here.
 *
 * @internal
 */
interface KeyQualifierResolverInterface
{
    /**
     * Whether the key is of type `multiValue`, whatever its variants list holds. Only such a key's
     * values travel as a list of qualified entries.
     */
    public function declaresQualifiers(KeyConfig $keyConfig): bool;

    /**
     * The variant names the key permits in the order it declares them, or null where it is not of
     * type `multiValue`.
     *
     * @return string[]|null
     */
    public function getVariants(KeyConfig $keyConfig): ?array;

    /**
     * The variants as the key declares them, each a name and an optional label, or null where the
     * key is not of type `multiValue`.
     *
     * @return array<int, array<string, string>>|null
     */
    public function getVariantDeclarations(KeyConfig $keyConfig): ?array;

    /**
     * Whether the `multiValue` key holds more than one value per variant, or null where the key is
     * not of type `multiValue`.
     */
    public function isMultiValue(KeyConfig $keyConfig): ?bool;

    /**
     * How many values one slot of the `multiValue` key may hold, or null where it declares no limit
     * or the key is not of type `multiValue`.
     */
    public function getMaximum(KeyConfig $keyConfig): ?int;

    /**
     * Whether the `multiValue` key permits a value carrying no variant, and that slot's optional label,
     * or null where the key is not of type `multiValue`. A key that states nothing permits it, as core
     * does, and so does a key read against a core that declares no such setting.
     */
    public function getDefaultVariant(KeyConfig $keyConfig): ?DefaultVariant;
}
