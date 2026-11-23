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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\Transfer;

use const JSON_THROW_ON_ERROR;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactDocument;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactDuplicator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParserInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\IdentifierGeneratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use function array_keys;
use function array_values;
use function explode;
use function json_encode;
use function sprintf;

/**
 * A file from another store is written under identifiers this installation hands out, and every
 * reference between its entries has to arrive at the regenerated one - a relation that still names
 * the file's identifier would resolve to nothing and be refused.
 *
 * @internal
 */
final class ArtifactDuplicatorTest extends Unit
{
    private const string GENERATED = '01GENERATED%d';

    private const string GROUP_IDENTIFIER = '01GROUP';

    private const string KEY_IDENTIFIER = '01KEY';

    private const string SUB_GROUP_IDENTIFIER = '01SUBGROUP';

    private const string COLLECTION_IDENTIFIER = '01COLLECTION';

    /**
     * The entity identifiers in the order the duplicator hands them out: sub groups, collections,
     * groups, keys, one entry each in this fixture.
     */
    private const string SUB_GROUP_FRESH = '01GENERATED1';

    private const string COLLECTION_FRESH = '01GENERATED2';

    private const string GROUP_FRESH = '01GENERATED3';

    private const string KEY_FRESH = '01GENERATED4';

    public function testEveryEntityIsRekeyedUnderAFreshIdentifier(): void
    {
        $duplicated = $this->duplicate($this->createArtifact());

        $expected = [
            ArtifactSection::SUB_GROUPS->value => self::SUB_GROUP_FRESH,
            ArtifactSection::COLLECTIONS->value => self::COLLECTION_FRESH,
            ArtifactSection::GROUPS->value => self::GROUP_FRESH,
            ArtifactSection::KEYS->value => self::KEY_FRESH,
        ];

        foreach ($expected as $section => $fresh) {
            $entries = $duplicated->getEntries(ArtifactSection::from($section));

            $this->assertSame([$fresh], array_keys($entries), $section);
            $this->assertSame(
                $fresh,
                array_values($entries)[0][ArtifactField::IDENTIFIER->value],
                $section
            );
        }
    }

    /**
     * The entry key of a relation is the pair of identifiers it joins, so it has to be rebuilt from
     * the regenerated ones: a key still built from the file's would never match the row the apply
     * writes.
     */
    public function testACollectionRelationNamesTheRegeneratedEndpoints(): void
    {
        $entries = $this->duplicate($this->createArtifact())
            ->getEntries(ArtifactSection::COLLECTION_GROUP_RELATIONS);
        $entry = array_values($entries)[0];

        $this->assertSame(
            [self::COLLECTION_FRESH, self::GROUP_FRESH],
            explode(ArtifactParserInterface::ENTRY_KEY_SEPARATOR, array_keys($entries)[0])
        );
        $this->assertSame(self::COLLECTION_FRESH, $entry[ArtifactField::COLLECTION->value]);
        $this->assertSame(self::GROUP_FRESH, $entry[ArtifactField::GROUP->value]);
        $this->assertSame(7, $entry[ArtifactField::SORTER->value]);
    }

    public function testAKeyAssignmentNamesTheRegeneratedGroupKeyAndSubGroup(): void
    {
        $entries = $this->duplicate($this->createArtifact())->getEntries(ArtifactSection::KEY_GROUP_RELATIONS);
        $entry = array_values($entries)[0];

        $this->assertSame(
            [self::GROUP_FRESH, self::KEY_FRESH],
            explode(ArtifactParserInterface::ENTRY_KEY_SEPARATOR, array_keys($entries)[0])
        );
        $this->assertSame(self::GROUP_FRESH, $entry[ArtifactField::GROUP->value]);
        $this->assertSame(self::KEY_FRESH, $entry[ArtifactField::KEY->value]);
        $this->assertSame(self::SUB_GROUP_FRESH, $entry[ArtifactField::SUB_GROUP->value]);
    }

    /**
     * An assignment that states no sub group must not gain one: supplying the field is what makes
     * the apply write it, and writing null would clear a tag the file says nothing about.
     */
    public function testAnAssignmentThatStatesNoSubGroupStillStatesNone(): void
    {
        $artifact = $this->createArtifact();
        unset($artifact[ArtifactSection::KEY_GROUP_RELATIONS->value][0][ArtifactField::SUB_GROUP->value]);

        $entry = array_values(
            $this->duplicate($artifact)->getEntries(ArtifactSection::KEY_GROUP_RELATIONS)
        )[0];

        $this->assertArrayNotHasKey(ArtifactField::SUB_GROUP->value, $entry);
    }

    /**
     * A reference to an entity the file does not carry is left as it stands, so the apply refuses it
     * for the endpoint it has always been missing rather than for one this rewrite renamed away.
     */
    public function testAReferenceToAnEntityTheFileDoesNotCarryIsLeftAsItIs(): void
    {
        $artifact = $this->createArtifact();
        $artifact[ArtifactSection::KEY_GROUP_RELATIONS->value][0][ArtifactField::KEY->value] = '01NOBODY';

        $entry = array_values(
            $this->duplicate($artifact)->getEntries(ArtifactSection::KEY_GROUP_RELATIONS)
        )[0];

        $this->assertSame('01NOBODY', $entry[ArtifactField::KEY->value]);
    }

    /**
     * The store entry is handed through untouched: in this mode the target keeps its own identity
     * and name, and nothing reads the file's.
     */
    public function testTheStoreEntryIsHandedThrough(): void
    {
        $duplicated = $this->duplicate($this->createArtifact());

        $this->assertSame('01STORE', $duplicated->getStoreIdentifier());
        $this->assertSame('ETIM', $duplicated->getStoreName());
    }

    /**
     * @param array<string, mixed> $artifact
     */
    private function duplicate(array $artifact): ArtifactDocument
    {
        $handed = 0;
        $duplicator = new ArtifactDuplicator(
            $this->makeEmpty(IdentifierGeneratorInterface::class, [
                'generate' => static function () use (&$handed): string {
                    ++$handed;

                    return sprintf(self::GENERATED, $handed);
                },
            ])
        );

        return $duplicator->duplicate(
            (new ArtifactParser())->parse((string) json_encode($artifact, JSON_THROW_ON_ERROR))
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function createArtifact(): array
    {
        return [
            ArtifactSection::STORE->value => [
                ArtifactField::IDENTIFIER->value => '01STORE',
                ArtifactField::NAME->value => 'ETIM',
                ArtifactField::DESCRIPTION->value => null,
            ],
            ArtifactSection::SUB_GROUPS->value => [
                [ArtifactField::IDENTIFIER->value => self::SUB_GROUP_IDENTIFIER, ArtifactField::NAME->value => 'Physical', ArtifactField::DESCRIPTION->value => null],
            ],
            ArtifactSection::COLLECTIONS->value => [
                [ArtifactField::IDENTIFIER->value => self::COLLECTION_IDENTIFIER, ArtifactField::NAME->value => 'Cables', ArtifactField::DESCRIPTION->value => null],
            ],
            ArtifactSection::GROUPS->value => [
                [ArtifactField::IDENTIFIER->value => self::GROUP_IDENTIFIER, ArtifactField::NAME->value => 'Measurements', ArtifactField::DESCRIPTION->value => null],
            ],
            ArtifactSection::KEYS->value => [
                [
                    ArtifactField::IDENTIFIER->value => self::KEY_IDENTIFIER,
                    ArtifactField::NAME->value => 'weight',
                    ArtifactField::TITLE->value => 'Weight',
                    ArtifactField::DESCRIPTION->value => null,
                    ArtifactField::TYPE->value => 'input',
                    ArtifactField::DEFINITION->value => ['fieldtype' => 'input'],
                    ArtifactField::ENABLED->value => true,
                ],
            ],
            ArtifactSection::COLLECTION_GROUP_RELATIONS->value => [
                [
                    ArtifactField::COLLECTION->value => self::COLLECTION_IDENTIFIER,
                    ArtifactField::GROUP->value => self::GROUP_IDENTIFIER,
                    ArtifactField::SORTER->value => 7,
                ],
            ],
            ArtifactSection::KEY_GROUP_RELATIONS->value => [
                [
                    ArtifactField::GROUP->value => self::GROUP_IDENTIFIER,
                    ArtifactField::KEY->value => self::KEY_IDENTIFIER,
                    ArtifactField::SORTER->value => 1,
                    ArtifactField::MANDATORY->value => false,
                    ArtifactField::SUB_GROUP->value => self::SUB_GROUP_IDENTIFIER,
                ],
            ],
        ];
    }
}
