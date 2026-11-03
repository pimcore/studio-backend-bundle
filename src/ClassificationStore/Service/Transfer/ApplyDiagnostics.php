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
    ): void {
        $identifier = $target->getIdentifier();
        $label = $this->label($section, $target->getName());

        if ($identifier !== null && isset($entriesByName[mb_strtolower($target->getName())])) {
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
