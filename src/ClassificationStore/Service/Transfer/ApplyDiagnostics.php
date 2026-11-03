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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function explode;
use function implode;
use function mb_strtolower;
use function sprintf;

/**
 * @internal
 */
final readonly class ApplyDiagnostics implements ApplyDiagnosticsInterface
{
    /**
     * What an entry of each section is called in the report.
     */
    private const array SECTION_LEVELS = [
        ArtifactSection::STORE->value => 'store',
        ArtifactSection::SUB_GROUPS->value => 'sub group',
        ArtifactSection::COLLECTIONS->value => 'collection',
        ArtifactSection::GROUPS->value => 'group',
        ArtifactSection::KEYS->value => 'key',
        ArtifactSection::COLLECTION_GROUP_RELATIONS->value => 'collection group relation',
        ArtifactSection::KEY_GROUP_RELATIONS->value => 'key assignment',
    ];

    /**
     * Whether a name of the level answers for one row of the store at most, which is what makes a
     * name the file reuses under another identifier a refusal rather than a second row.
     *
     * Keys are the exception, and are listed as such rather than left out: key names are
     * deliberately non-unique - the duplicate-name guard was withdrawn in the requirements register
     * because it would make a call that succeeds today start failing - so two keys of one store may
     * both be called `weight`. Refusing the one the file does not name, because the file names the
     * other, would roll back a whole import over a row that is simply target only.
     */
    private const array NAME_IS_UNIQUE_PER_STORE = [
        ArtifactSection::SUB_GROUPS->value => true,
        ArtifactSection::COLLECTIONS->value => true,
        ArtifactSection::GROUPS->value => true,
        ArtifactSection::KEYS->value => false,
    ];

    /**
     * {@inheritdoc}
     */
    public function recordUnresolvedEndpoints(ApplyContext $context, string $label, array $missing): void
    {
        if ($context->isDryRun() && $missing === []) {
            $context->record(ApplyOutcome::CREATED, $label);

            return;
        }

        $context->record(
            ApplyOutcome::CONFLICT,
            $missing === []
                ? sprintf('%s: one of the two entries it names does not exist', $label)
                : sprintf(
                    '%s: it names "%s", which exists neither in the file nor in the store',
                    $label,
                    implode('" and "', $missing)
                )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function recordUnmatchedTarget(
        ApplyContext $context,
        ArtifactSection $section,
        array $entriesByName,
        SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig $target,
        array $claimed = [],
    ): void {
        $identifier = $target->getIdentifier();
        $label = $this->label($section, $target->getName());
        $nameIsUnique = self::NAME_IS_UNIQUE_PER_STORE[$section->value] ?? false;
        $sharedEntry = $entriesByName[mb_strtolower($target->getName())] ?? null;
        $sharedEntryIsUnanswered = $sharedEntry !== null && !isset($claimed[$sharedEntry]);

        if ($nameIsUnique && $identifier !== null && $sharedEntryIsUnanswered) {
            $context->record(
                ApplyOutcome::CONFLICT,
                sprintf('%s already carries another identifier than the one the file gives it', $label)
            );

            return;
        }

        $context->record(ApplyOutcome::TARGET_ONLY, $label);

        if ($identifier !== null && $target->getId() !== null) {
            // a relation of the file may still name it, so it stays resolvable
            $context->register($section, $identifier, $target->getId());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function label(ArtifactSection $section, string $name): string
    {
        return sprintf('%s "%s"', self::SECTION_LEVELS[$section->value], $name);
    }

    /**
     * {@inheritdoc}
     */
    public function relationLabel(ArtifactSection $section, string $entryKey): string
    {
        return sprintf(
            '%s "%s"',
            self::SECTION_LEVELS[$section->value],
            implode(' -> ', explode(ArtifactParserInterface::ENTRY_KEY_SEPARATOR, $entryKey))
        );
    }
}
