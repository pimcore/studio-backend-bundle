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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function array_keys;
use function json_encode;

/**
 * The parser is the only thing between an uploaded file and the writes an apply issues, so what it
 * refuses is what the endpoint answers 422 with.
 *
 * @internal
 */
final class ArtifactParserTest extends Unit
{
    private const string STORE_IDENTIFIER = '01JBQ8X9Z0STORESTORESTORE';

    private const string GROUP_IDENTIFIER = '01JBQ8X9Z0TUVWXYZ23456789';

    private const string KEY_IDENTIFIER = '01JBQ8X9Z0KEYKEYKEYKEYKEY';

    public function testTheSixSectionsAreKeyedByWhatMatchesAnEntry(): void
    {
        $document = (new ArtifactParser())->parse($this->encode($this->createArtifact()));

        $this->assertSame(self::STORE_IDENTIFIER, $document->getStoreIdentifier());
        $this->assertSame('ETIM', $document->getStoreName());
        $this->assertSame('The ETIM classification', $document->getStoreDescription());
        $this->assertSame(
            [self::GROUP_IDENTIFIER],
            array_keys($document->getEntries(ArtifactSection::GROUPS))
        );
        $this->assertSame(
            [self::GROUP_IDENTIFIER . "\0" . self::KEY_IDENTIFIER],
            array_keys($document->getEntries(ArtifactSection::KEY_GROUP_RELATIONS))
        );
    }

    public function testAFileThatIsNotJsonIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ArtifactParser())->parse('{"store": ');
    }

    public function testAFileWithoutAStoreEntryIsRefused(): void
    {
        $artifact = $this->createArtifact();
        unset($artifact['store']);

        $this->expectException(InvalidArgumentException::class);

        (new ArtifactParser())->parse($this->encode($artifact));
    }

    public function testAFileMissingASectionIsRefused(): void
    {
        $artifact = $this->createArtifact();
        unset($artifact['keys']);

        $this->expectException(InvalidArgumentException::class);

        (new ArtifactParser())->parse($this->encode($artifact));
    }

    public function testAnEntryWithoutAnIdentifierIsRefused(): void
    {
        $artifact = $this->createArtifact();
        unset($artifact['groups'][0]['identifier']);

        $this->expectException(InvalidArgumentException::class);

        (new ArtifactParser())->parse($this->encode($artifact));
    }

    /**
     * Two entries under one key would silently drop one of them, which is worse than refusing the
     * file.
     */
    public function testASectionNamingTheSameEntryTwiceIsRefused(): void
    {
        $artifact = $this->createArtifact();
        $artifact['groups'][] = $artifact['groups'][0];

        $this->expectException(InvalidArgumentException::class);

        (new ArtifactParser())->parse($this->encode($artifact));
    }

    /**
     * The three per assignment columns are written only when the entry supplies them, so the parser
     * has to keep an omitted field apart from one supplied as null - which is what the model's own
     * initialized guards gate the write on.
     */
    public function testAnOmittedPerAssignmentFieldIsNotSupplied(): void
    {
        $assignment = (new ArtifactParser())->readAssignmentEntry([
            'group' => self::GROUP_IDENTIFIER,
            'key' => self::KEY_IDENTIFIER,
            'sorter' => 1,
            'mandatory' => false,
        ]);

        $this->assertFalse($assignment->isMetadataSupplied());
        $this->assertFalse($assignment->isDefinitionOverrideSupplied());
        $this->assertFalse($assignment->isSubGroupSupplied());
    }

    public function testAPerAssignmentFieldSuppliedAsNullIsSupplied(): void
    {
        $assignment = (new ArtifactParser())->readAssignmentEntry([
            'group' => self::GROUP_IDENTIFIER,
            'key' => self::KEY_IDENTIFIER,
            'metadata' => null,
            'definitionOverride' => null,
            'subGroup' => null,
        ]);

        $this->assertTrue($assignment->isMetadataSupplied());
        $this->assertTrue($assignment->isDefinitionOverrideSupplied());
        $this->assertTrue($assignment->isSubGroupSupplied());
        $this->assertNull($assignment->getMetadata());
        $this->assertSame(0, $assignment->getSorter());
    }

    /**
     * A value of the wrong shape is refused rather than read as the default an absent field stands
     * for: read as a default, `"name": 17` renames the group to "17", `"enabled": "true"` disables
     * the key it was meant to enable, and `"mandatory": "true"` clears the constraint it was meant
     * to set - every one of them a change, under a file that is wrong rather than a file that says
     * so.
     *
     * @dataProvider wronglyShapedEntries
     *
     * @param array<string, mixed> $overrides
     */
    public function testAFieldSuppliedInTheWrongShapeIsRefused(string $section, array $overrides): void
    {
        $artifact = $this->createArtifact();
        $artifact[$section][0] = [...$artifact[$section][0], ...$overrides];

        $this->expectException(InvalidArgumentException::class);

        (new ArtifactParser())->parse($this->encode($artifact));
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public function wronglyShapedEntries(): array
    {
        return [
            'a group named with a number' => ['groups', ['name' => 17]],
            'an empty group name' => ['groups', ['name' => '']],
            'a group description that is not a string' => ['groups', ['description' => 17]],
            'a key enabled flag that is a string' => ['keys', ['enabled' => 'true']],
            'a key title that is not a string' => ['keys', ['title' => 17]],
            'a key type that is missing' => ['keys', ['type' => null]],
            'a key definition that is not an object' => ['keys', ['definition' => 'input']],
            'a key localized flag that is a string' => ['keys', ['localized' => 'true']],
            'a key localized flag that is a number' => ['keys', ['localized' => 1]],
            'a mandatory flag that is a string' => ['keyGroupRelations', ['mandatory' => 'true']],
            'a sorter that is a string' => ['keyGroupRelations', ['sorter' => '1']],
            'a sub group that is not a string' => ['keyGroupRelations', ['subGroup' => 17]],
        ];
    }

    /**
     * The readers are reachable on their own, so each guards the fields it reads rather than
     * trusting that the file went through {@see ArtifactParser::parse()} first.
     */
    public function testANonBooleanEnabledFlagIsRefusedByTheKeyReader(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ArtifactParser())->readKeyEntry([
            'identifier' => self::KEY_IDENTIFIER,
            'name' => 'weight',
            'type' => 'input',
            'enabled' => 'true',
        ]);
    }

    public function testANonBooleanMandatoryFlagIsRefusedByTheAssignmentReader(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ArtifactParser())->readAssignmentEntry([
            'group' => self::GROUP_IDENTIFIER,
            'key' => self::KEY_IDENTIFIER,
            'mandatory' => 'true',
        ]);
    }

    /**
     * A key row whose `enabled` column was never written reads as disabled, which is what it is.
     */
    public function testAKeyEntryWithoutAnEnabledFlagReadsAsDisabled(): void
    {
        $key = (new ArtifactParser())->readKeyEntry([
            'identifier' => self::KEY_IDENTIFIER,
            'name' => 'weight',
            'type' => 'input',
            'enabled' => null,
        ]);

        $this->assertFalse($key->isEnabled());
        $this->assertNull($key->getDefinition());
    }

    /**
     * `localized` is read back as the tri-state it is: an entry that leaves it out and one that
     * supplies it as null both read as no flag, which is what a file written before the flag existed
     * carries - and an explicit false stays false rather than being read as that same no flag.
     *
     * @dataProvider localizedEntryProvider
     *
     * @param array<string, mixed> $overrides
     */
    public function testTheLocalizedFlagOfAKeyEntryIsReadAsATriState(array $overrides, ?bool $expected): void
    {
        $key = (new ArtifactParser())->readKeyEntry([
            'identifier' => self::KEY_IDENTIFIER,
            'name' => 'weight',
            'type' => 'input',
            ...$overrides,
        ]);

        $this->assertSame($expected, $key->getLocalized());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: ?bool}>
     */
    public static function localizedEntryProvider(): array
    {
        return [
            'an absent member reads as no flag' => [[], null],
            'an explicit null reads as no flag' => [['localized' => null], null],
            'an explicit true reads as true' => [['localized' => true], true],
            'an explicit false reads as false' => [['localized' => false], false],
        ];
    }

    /**
     * The reader guards the field on its own, the way it guards `enabled`.
     */
    public function testANonBooleanLocalizedFlagIsRefusedByTheKeyReader(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ArtifactParser())->readKeyEntry([
            'identifier' => self::KEY_IDENTIFIER,
            'name' => 'weight',
            'type' => 'input',
            'localized' => 'true',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createArtifact(): array
    {
        return [
            'store' => [
                'identifier' => self::STORE_IDENTIFIER,
                'name' => 'ETIM',
                'description' => 'The ETIM classification',
            ],
            'subGroups' => [],
            'collections' => [],
            'groups' => [
                ['identifier' => self::GROUP_IDENTIFIER, 'name' => 'Measurements', 'description' => null],
            ],
            'keys' => [
                [
                    'identifier' => self::KEY_IDENTIFIER,
                    'name' => 'weight',
                    'title' => 'Weight',
                    'description' => null,
                    'type' => 'input',
                    'definition' => ['fieldtype' => 'input', 'title' => 'Weight'],
                    'enabled' => true,
                ],
            ],
            'collectionGroupRelations' => [],
            'keyGroupRelations' => [
                [
                    'group' => self::GROUP_IDENTIFIER,
                    'key' => self::KEY_IDENTIFIER,
                    'sorter' => 1,
                    'mandatory' => false,
                    'metadata' => null,
                    'definitionOverride' => null,
                    'subGroup' => null,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $artifact
     */
    private function encode(array $artifact): string
    {
        return json_encode($artifact, JSON_THROW_ON_ERROR);
    }
}
