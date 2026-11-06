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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Document\Data\Adapter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Adapter\PageAdapter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentFieldKeys;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Model\Document\Page;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class PageAdapterTest extends Unit
{
    public function testSetDataAcceptsPurelyNumericEditableNames(): void
    {
        $setEditables = [];
        $document = $this->createMock(Page::class);
        $document->method('setRawEditable')->willReturnCallback(
            function (string $name, string $type, mixed $data) use (&$setEditables, $document): Page {
                $setEditables[$name] = [$type, $data];

                return $document;
            }
        );

        $adapter = new PageAdapter(
            $this->createMock(SecurityServiceInterface::class),
            $this->createMock(StaticPageGenerator::class)
        );

        // json_decode() turns the purely numeric editable name "123" into the integer array key 123
        $adapter->setData(
            $document,
            [
                DocumentFieldKeys::EDITABLE_DATA->value => [
                    '123' => ['type' => 'input', 'data' => 'numeric'],
                    'headline' => ['type' => 'input', 'data' => 'text'],
                ],
            ],
            $this->createMock(UserInterface::class)
        );

        $this->assertSame(
            [
                '123' => ['input', 'numeric'],
                'headline' => ['input', 'text'],
            ],
            $setEditables
        );
    }
}
