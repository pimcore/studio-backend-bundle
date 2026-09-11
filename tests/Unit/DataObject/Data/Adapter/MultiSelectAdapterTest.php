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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\MultiSelectAdapter;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class MultiSelectAdapterTest extends Unit
{
    /**
     * Regression test: a replace patch with data null clears the field instead of throwing.
     *
     * @throws Exception
     */
    public function testPatchReplaceWithNullReturnsNull(): void
    {
        $this->assertNull($this->callAdapter(['color' => ['action' => 'replace', 'data' => null]]));
    }

    /**
     * @throws Exception
     */
    public function testPatchReplaceWithValuesReturnsValues(): void
    {
        $result = $this->callAdapter(['color' => ['action' => 'replace', 'data' => ['red', 'green']]]);

        $this->assertSame(['red', 'green'], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNullWhenValueIsNotAnArray(): void
    {
        $this->assertNull($this->callAdapter(['color' => null]));
    }

    /**
     * @throws Exception
     */
    private function callAdapter(array $data): ?array
    {
        $adapter = new MultiSelectAdapter();

        return $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(Multiselect::class),
            'color',
            $data,
            $this->makeEmpty(UserInterface::class),
            isPatch: true
        );
    }
}
