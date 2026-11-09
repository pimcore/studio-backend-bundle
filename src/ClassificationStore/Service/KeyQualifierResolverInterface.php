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
 * The one place a key's qualified-value declaration is read, so that the layout paths, the key
 * configuration endpoints and the object data path cannot drift apart on what `variants` and
 * `multiValue` mean.
 *
 * Both are reserved members of the key's `definition` object, the way `indexed` already is, and
 * both are published raw: an ordered list of variant names and a boolean, or NULL where the key
 * declares nothing. Nothing is resolved against the engine of the key's store here - a store whose
 * engine reports neither capability still stores and reports the declaration.
 *
 * @internal
 */
interface KeyQualifierResolverInterface
{
    /**
     * The reserved member holding the variant names a key permits, named once so that the key
     * configuration endpoints, the layout paths and the object data path spell it the same way.
     */
    public const string VARIANTS_MEMBER = 'variants';

    /**
     * The reserved member holding whether a key holds more than one value per variant.
     */
    public const string MULTI_VALUE_MEMBER = 'multiValue';

    /**
     * Whether the key's declaration permits more than the one slot it always had, which is what
     * makes its values travel as a list of qualified entries rather than as a single value: a
     * variant list holding a usable name, or `multiValue` declared true.
     *
     * The declaration is read, not its mere presence. A key permits exactly the single default slot
     * - and keeps serialising as a single value - when it names no usable variant AND does not
     * declare `multiValue` true: `variants: []` permits no variant beside the default one, but a key
     * naming a variant and declaring `multiValue: false` still carries one value per named variant
     * and still travels as a list of entries. A key whose blob carries neither member names no
     * variant and declares no `multiValue`, as does every key of every store that existed before
     * these members did.
     */
    public function declaresQualifiers(KeyConfig $keyConfig): bool;

    /**
     * The variant names the key permits in the order it declares them, or null where it declares
     * none. A name that is not a non-empty string is left out rather than published as one, so that
     * the default variant - which carries no name - stays distinguishable from a declared one.
     *
     * The member has to be an ordered list to be a declaration at all: a member holding anything
     * else - a string, a boolean, or a JSON object whose members would otherwise be read as the
     * names - declares none, so that nothing but a list can make a key's values travel as entries.
     *
     * @return string[]|null
     */
    public function getVariants(KeyConfig $keyConfig): ?array;

    /**
     * The key's own `multiValue` setting, or null where it declares none.
     */
    public function isMultiValue(KeyConfig $keyConfig): ?bool;
}
