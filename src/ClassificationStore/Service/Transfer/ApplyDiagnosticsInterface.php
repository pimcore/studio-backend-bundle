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
     * Whether what an unmatched row is cannot be told until every walk of the level has finished
     * claiming: the row shares its name with an entry of the file, so the verdict turns on whether
     * some other row of the level answers for that entry - which is a fact about the level, not
     * about the row a walk happens to reach first.
     *
     * A caller walking a level in chunks asks this before reporting a row it could not match, and
     * holds back the rows it answers true for. Only those: a level whose names are not unique, or a
     * row sharing its name with nothing in the file, is target only whatever the rest of the level
     * turns out to say, and the big section - keys, whose names are deliberately not unique - never
     * awaits anything.
     *
     * @param array<string, string> $entriesByName
     */
    public function unmatchedTargetAwaitsTheLevel(
        ArtifactSection $section,
        array $entriesByName,
        SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig $target,
    ): bool;

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
     * `$claimed` maps the entry keys this section has matched to the row id that matched them, and
     * has to be the ledger of the WHOLE level for the second condition to hold. Reading it while a
     * walk is still running decides the refusal by row order: two rows named `Measurements` carrying
     * the identifiers `A` and `B`, a file naming only `A`, and `A` on the higher row id, would refuse
     * `B` over an entry `A` was about to claim one row later. {@see self::unmatchedTargetAwaitsTheLevel()}
     * is how a chunked walk tells which rows it may not report yet.
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
