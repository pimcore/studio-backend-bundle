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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\BlockAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class BlockAdapterTest extends Unit
{
    /**
     * Regression test: an empty block arrives as an explicit null (e.g. from an
     * objectbrick without stored data) and must clear the field instead of throwing
     * a TypeError.
     *
     * @see https://github.com/pimcore/platform-version/issues/446
     *
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNullWhenBlockDataIsNull(): void
    {
        $this->assertNull($this->callAdapter(['myBlock' => null]));
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNullWhenBlockDataIsNotAnArray(): void
    {
        $this->assertNull($this->callAdapter(['myBlock' => 'not-an-array']));
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterReturnsNullForNonBlockFieldDefinition(): void
    {
        $adapter = new BlockAdapter(
            $this->makeEmpty(DataAdapterServiceInterface::class),
            $this->makeEmpty(DataServiceInterface::class)
        );

        $result = $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            new Input(),
            'myBlock',
            ['myBlock' => [['text' => 'value']]],
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    public function testGetDataForSetterProcessesBlockItems(): void
    {
        $result = $this->callAdapter(['myBlock' => [['text' => 'hello world']]]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('text', $result[0]);

        $blockElement = $result[0]['text'];
        $this->assertInstanceOf(BlockElement::class, $blockElement);
        $this->assertSame('text', $blockElement->getName());
        $this->assertSame('input', $blockElement->getType());
        $this->assertSame('hello world', $blockElement->getData());
    }

    /**
     * @throws Exception
     */
    private function callAdapter(array $data): ?array
    {
        $elementAdapter = $this->makeEmpty(SetterDataInterface::class, [
            'getDataForSetter' => static fn (
                Concrete $element,
                mixed $fieldDefinition,
                string $key,
                array $data
            ): mixed => $data[$key] ?? null,
        ]);

        $adapter = new BlockAdapter(
            $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $elementAdapter,
            ]),
            $this->makeEmpty(DataServiceInterface::class)
        );

        $textDefinition = new Input();
        $textDefinition->setName('text');

        $blockDefinition = $this->makeEmpty(Block::class, [
            'getName' => 'myBlock',
            'getFieldDefinitions' => ['text' => $textDefinition],
        ]);

        return $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class, ['getClassId' => 'test-class']),
            $blockDefinition,
            'myBlock',
            $data,
            $this->makeEmpty(UserInterface::class)
        );
    }
}
