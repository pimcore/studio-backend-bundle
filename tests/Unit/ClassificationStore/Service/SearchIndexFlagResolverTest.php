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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexFlagResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexFlagResolverInterface;
use function json_encode;

/**
 * The read rule of the reserved `indexed` member, which is the whole of what the flag means: only
 * boolean `false` excludes an assignment from the search index, and every other state - true, a non
 * boolean, an absent member, a NULL column - leaves it indexed, which is what every assignment
 * written before the member existed is.
 *
 * The seeding rule is checked against the same rule rather than on its own, because the two have to
 * agree: what a created assignment inherits from its key has to read back as the key's own answer.
 *
 * @internal
 */
final class SearchIndexFlagResolverTest extends Unit
{
    private const string MEMBER = 'indexed';

    private const string SIBLING = 'source';

    private const string SIBLING_VALUE = 'erp';

    private SearchIndexFlagResolver $resolver;

    public function _before(): void
    {
        $this->resolver = new SearchIndexFlagResolver();
    }

    /**
     * The member name is published once, so the search index and this bundle bind to one spelling.
     */
    public function testTheMemberIsNamedOnceAndSpelledIndexed(): void
    {
        $this->assertSame(self::MEMBER, SearchIndexFlagResolverInterface::INDEXED_MEMBER);
    }

    /**
     * @dataProvider readRuleProvider
     *
     * @param array<string, mixed>|null $metadata
     */
    public function testOnlyBooleanFalseExcludesAnAssignment(?array $metadata, bool $indexed): void
    {
        $this->assertSame($indexed, $this->resolver->isIndexed($metadata));
    }

    /**
     * @return array<string, array{0: array<string, mixed>|null, 1: bool}>
     */
    public static function readRuleProvider(): array
    {
        return [
            'false excludes it' => [[self::MEMBER => false], false],
            'true indexes it' => [[self::MEMBER => true], true],
            'a non boolean indexes it' => [[self::MEMBER => 'no'], true],
            'a member present as null indexes it' => [[self::MEMBER => null], true],
            'an absent member indexes it' => [[self::SIBLING => self::SIBLING_VALUE], true],
            'an empty object indexes it' => [[], true],
            'a null column indexes it' => [null, true],
        ];
    }

    /**
     * @dataProvider flagPresenceProvider
     *
     * @param array<string, mixed>|null $metadata
     */
    public function testOnlyAPresentMemberCountsAsAnAnswerOfItsOwn(?array $metadata, bool $carried): void
    {
        $this->assertSame($carried, $this->resolver->carriesFlag($metadata));
    }

    /**
     * @return array<string, array{0: array<string, mixed>|null, 1: bool}>
     */
    public static function flagPresenceProvider(): array
    {
        return [
            'false is an answer' => [[self::MEMBER => false], true],
            'true is an answer' => [[self::MEMBER => true], true],
            'a member present as null is an answer' => [[self::MEMBER => null], true],
            'another member is not' => [[self::SIBLING => self::SIBLING_VALUE], false],
            'an empty object is not' => [[], false],
            'a null column is not' => [null, false],
        ];
    }

    /**
     * A key stating the member hands it to a created assignment whichever way that assignment's own
     * metadata is missing it, and the members it does carry survive alongside it.
     *
     * @dataProvider seedProvider
     *
     * @param array<string, mixed>|null $metadata
     * @param array<string, mixed>|null $expected
     */
    public function testTheKeyDefaultFillsInForAMissingMember(
        ?array $metadata,
        string $keyDefinition,
        ?array $expected,
    ): void {
        $this->assertSame($expected, $this->resolver->withKeyDefault($metadata, $keyDefinition));
    }

    /**
     * @return array<string, array{0: array<string, mixed>|null, 1: string, 2: array<string, mixed>|null}>
     */
    public static function seedProvider(): array
    {
        return [
            'a null column takes the default' => [
                null,
                '{"fieldtype":"input","indexed":false}',
                [self::MEMBER => false],
            ],
            'an object without the member takes it alongside its own' => [
                [self::SIBLING => self::SIBLING_VALUE],
                '{"fieldtype":"input","indexed":false}',
                [self::SIBLING => self::SIBLING_VALUE, self::MEMBER => false],
            ],
            'a key stating true seeds true' => [
                null,
                '{"fieldtype":"input","indexed":true}',
                [self::MEMBER => true],
            ],
            'a key stating a non boolean seeds it verbatim' => [
                null,
                '{"fieldtype":"input","indexed":"no"}',
                [self::MEMBER => 'no'],
            ],
            'a key stating nothing seeds nothing' => [
                null,
                '{"fieldtype":"input"}',
                null,
            ],
            'an empty definition seeds nothing' => [null, '', null],
        ];
    }

    /**
     * The two halves of the rule over one set of fixtures: what the seeding writes for a key reads
     * back as that key's own answer, so the write surface and whatever honours the flag later cannot
     * disagree about a seeded assignment.
     *
     * @dataProvider agreementProvider
     *
     * @param array<string, mixed> $keyDefinition
     */
    public function testASeededAssignmentReadsAsItsKeyDoes(array $keyDefinition): void
    {
        $seeded = $this->resolver->withKeyDefault(
            [self::SIBLING => self::SIBLING_VALUE],
            (string) json_encode($keyDefinition)
        );

        $this->assertNotNull($seeded, 'The fixture is a key that states the member.');
        $this->assertSame($this->resolver->isIndexed($keyDefinition), $this->resolver->isIndexed($seeded));
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function agreementProvider(): array
    {
        return [
            'a key that excludes' => [[self::MEMBER => false]],
            'a key that indexes' => [[self::MEMBER => true]],
            'a key stating a non boolean' => [[self::MEMBER => 'no']],
        ];
    }
}
