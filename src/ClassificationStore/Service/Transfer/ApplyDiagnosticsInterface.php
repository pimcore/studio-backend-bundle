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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;

/**
 * How an entry an apply could not act on is reported: what it is called in the report, and which
 * outcome an unmatched row or an unresolved relation is recorded under.
 *
 * @internal
 */
interface ApplyDiagnosticsInterface
{
    /**
     * A relation whose endpoints the run could not resolve to row ids.
     *
     * `$missing` names the endpoints that exist in neither the file nor the store. An endpoint left
     * out of it is one the file declares and no row has been written for yet, which only a dry run
     * can be in - it writes nothing, so it resolves no new id.
     *
     * A dry run therefore reports the relation as the creation it would be once the entries it
     * names are written, and refuses it only where one of them is named nowhere. A real run refuses
     * it either way, because a relation row cannot be written without both of its ends.
     *
     * @param list<string> $missing
     */
    public function recordUnresolvedEndpoints(ApplyContext $context, string $label, array $missing): void;

    /**
     * A target row the file does not answer for. It is left in place either way; what is refused is
     * a row whose name the file reuses under another identifier, because applying the file would
     * then have to write two rows under one name.
     *
     * That refusal holds under two conditions. The level's name has to be unique per store - key
     * names are not, by decision, so a key is always left in place as target only. And the entry
     * that shares the name has to be one no row has answered for yet: an entry its own identifier
     * owner has already claimed is not a name this file is asking for twice, which leaves the row
     * that merely shares the name as the target-only row it is.
     *
     * `$claimed` maps the entry keys this section has already matched to the row id that matched
     * them.
     *
     * @param array<string, string> $entriesByName
     * @param array<string, int> $claimed
     */
    public function recordUnmatchedTarget(
        ApplyContext $context,
        ArtifactSection $section,
        array $entriesByName,
        SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig $target,
        array $claimed = [],
    ): void;

    /**
     * What one entry of the section is called in the report.
     */
    public function label(ArtifactSection $section, string $name): string;

    /**
     * What one entry of a relation section is called in the report, from the key it is held by.
     */
    public function relationLabel(ArtifactSection $section, string $entryKey): string;
}
