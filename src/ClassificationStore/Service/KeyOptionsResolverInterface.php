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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUnits;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\OptionsUnavailableReason;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * The one place a key's option vocabulary and unit vocabulary are resolved for the configuration
 * surfaces: from the key's own definition for a key configuration, and from the effective definition
 * of the assignment, its override merged over the key's own, for a key group relation.
 *
 * Only a key whose own definition is a select or multiselect has options; a wrapper type's delegate
 * is not looked inside. No data object is involved, so a provider is asked for static options only.
 *
 * Every method takes a key configuration that has been loaded, as the repository returns it: the
 * key's `type` has no default on the model, and the two hydrators that call this read that same
 * property for the published `type` before they get here, so an unhydrated configuration never
 * reaches a resolution.
 *
 * @internal
 */
interface KeyOptionsResolverInterface
{
    /**
     * The key's full option vocabulary as resolved for this request, whatever its source, or null
     * where the key is not select-like or {@see self::getOptionsUnavailableReason()} says why not.
     *
     * @return SelectOption[]|null
     */
    public function getOptions(KeyConfig $keyConfig): ?array;

    /**
     * Why a select-like key publishes no options, or null where nothing failed.
     */
    public function getOptionsUnavailableReason(KeyConfig $keyConfig): ?OptionsUnavailableReason;

    /**
     * The unit vocabulary the key's own definition exposes, or null where its type holds no units.
     */
    public function getUnits(KeyConfig $keyConfig): ?KeyUnits;

    /**
     * The options of the assignment, narrowed to its option subset, whatever their source - the same
     * resolution as {@see self::getOptions()} otherwise.
     *
     * @return SelectOption[]|null
     */
    public function getRelationOptions(KeyGroupRelation $relation): ?array;

    /**
     * Why a select-like assignment publishes no options, or null where nothing failed.
     */
    public function getRelationOptionsUnavailableReason(KeyGroupRelation $relation): ?OptionsUnavailableReason;

    /**
     * The unit vocabulary of the assignment, its override applied, or null where its type holds no units.
     */
    public function getRelationUnits(KeyGroupRelation $relation): ?KeyUnits;
}
