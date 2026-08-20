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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Repository;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\ObjectDependenciesRepository;
use ReflectionMethod;

/**
 * Exercises the cross-class pagination windowing math directly, since the surrounding
 * getObjectsReferencingUser() also depends on real, dynamically-resolved DataObject class
 * listings that this bundle's unit test suite has no fixtures for. The windowing itself -
 * deciding which slice of a given class's matches (if any) falls inside the requested
 * cross-class offset/limit - is pure integer arithmetic and fully testable in isolation.
 *
 * @internal
 */
final class ObjectDependenciesRepositoryTest extends Unit
{
    private ReflectionMethod $resolveClassWindow;

    protected function _before(): void
    {
        $this->resolveClassWindow = new ReflectionMethod(ObjectDependenciesRepository::class, 'resolveClassWindow');
    }

    /**
     * @dataProvider windowProvider
     */
    public function testResolveClassWindow(
        int $matchesBeforeCurrentClass,
        int $classMatchCount,
        int $offset,
        int $limit,
        int $stillNeeded,
        ?array $expected
    ): void {
        $repository = new ObjectDependenciesRepository();

        $result = $this->resolveClassWindow->invoke(
            $repository,
            $matchesBeforeCurrentClass,
            $classMatchCount,
            $offset,
            $limit,
            $stillNeeded
        );

        $this->assertSame($expected, $result);
    }

    public function windowProvider(): array
    {
        return [
            'first class, first page, class larger than page' => [0, 500, 0, 50, 50, [0, 50]],
            'class fully precedes the requested window' => [0, 30, 50, 50, 50, null],
            'class fully follows the requested window' => [200, 50, 0, 50, 50, null],
            'requested window starts inside this class, only its tail is taken' => [0, 30, 20, 50, 50, [20, 10]],
            'partial overlap in the middle of a multi-class sequence' => [100, 30, 110, 50, 50, [10, 20]],
            'deep offset entirely inside a single large class' => [0, 50000, 49950, 50, 50, [49950, 50]],
            'true last page, class starts exactly where the window does' => [49950, 50, 49950, 50, 50, [0, 50]],
            'range overlaps but the remaining page budget is already exhausted' => [0, 100, 0, 50, 0, null],
            'class could offer more, but only a few more items are needed to fill the page' => [0, 100, 0, 50, 10, [0, 10]],
        ];
    }
}
