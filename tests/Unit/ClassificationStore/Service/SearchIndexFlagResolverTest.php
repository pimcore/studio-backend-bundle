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

/**
 * The read rule of the reserved `indexed` member, which is the whole of what the flag means: it is
 * read from the key's own `definition` and nowhere else, only boolean `false` excludes the key, and
 * every other state - true, a non boolean, an absent member, a definition that does not parse, no
 * definition at all - leaves it indexed, which is what every key written before the member existed
 * is.
 *
 * @internal
 */
final class SearchIndexFlagResolverTest extends Unit
{
    private const string MEMBER = 'indexed';

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
     */
    public function testOnlyBooleanFalseExcludesAKey(?string $keyDefinition, bool $indexed): void
    {
        $this->assertSame($indexed, $this->resolver->isIndexed($keyDefinition));
    }

    /**
     * @return array<string, array{0: ?string, 1: bool}>
     */
    public static function readRuleProvider(): array
    {
        return [
            'false excludes the key' => ['{"fieldtype":"input","indexed":false}', false],
            'true indexes it' => ['{"fieldtype":"input","indexed":true}', true],
            'a non boolean indexes it' => ['{"fieldtype":"input","indexed":"no"}', true],
            'a member present as null indexes it' => ['{"fieldtype":"input","indexed":null}', true],
            'an absent member indexes it' => ['{"fieldtype":"input"}', true],
            'an empty definition object indexes it' => ['{}', true],
            'the empty definition a new key carries indexes it' => ['[]', true],
            'a definition that does not parse indexes it' => ['not json', true],
            'an empty definition string indexes it' => ['', true],
            'a scalar definition indexes it' => ['"input"', true],
            'no definition at all indexes it' => [null, true],
        ];
    }
}
