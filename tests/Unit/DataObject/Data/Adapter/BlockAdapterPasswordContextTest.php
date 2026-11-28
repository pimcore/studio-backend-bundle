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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\BlockAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\PasswordAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldCollectionData;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;

/**
 * Checks that the block adapter tells the password adapter when a block sits in a rebuilt container.
 *
 * @internal
 */
final class BlockAdapterPasswordContextTest extends Unit
{
    public function testPasswordInsideBlockOfFieldCollectionItemIsRejectedWhenUnchanged(): void
    {
        $this->expectException(ValidationException::class);

        $this->buildBlockAdapter()->getDataForSetter(
            $this->buildOwner(),
            $this->buildBlock(),
            'blocks',
            ['blocks' => [['secret' => '******']]],
            $this->makeEmpty(UserInterface::class),
            new FieldContextData($this->buildItem())
        );
    }

    public function testPasswordInsideTopLevelBlockIsStillAccepted(): void
    {
        $result = $this->buildBlockAdapter()->getDataForSetter(
            $this->buildOwner(),
            $this->buildBlock(),
            'blocks',
            ['blocks' => [['secret' => 'a-new-password']]],
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertSame('a-new-password', $result[0]['secret']->getData());
    }

    public function testUnchangedPasswordInsideNewTopLevelBlockItemStoresNothing(): void
    {
        $result = $this->buildBlockAdapter()->getDataForSetter(
            $this->buildOwner(),
            $this->buildBlock(),
            'blocks',
            ['blocks' => [['secret' => '******']]],
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertNull($result[0]['secret']->getData());
    }

    private function buildBlockAdapter(): BlockAdapter
    {
        $passwordAdapter = new PasswordAdapter();
        $adapterService = $this->makeEmpty(DataAdapterServiceInterface::class, [
            'tryDataAdapter' => static fn (string $type) => $type === 'password' ? $passwordAdapter : null,
        ]);

        return new BlockAdapter($adapterService, $this->makeEmpty(DataServiceInterface::class));
    }

    private function buildBlock(): Block
    {
        $password = new Password();
        $password->setName('secret');

        $block = new Block();
        $block->setName('blocks');
        $block->setChildren([$password]);

        return $block;
    }

    private function buildOwner(): Concrete
    {
        return new class extends Concrete {
            public function getClassId(): string
            {
                return 'x';
            }

            public function get(string $fieldName, ?string $language = null): mixed
            {
                return null;
            }
        };
    }

    private function buildItem(): FieldCollectionData
    {
        return new class extends FieldCollectionData {
            public function get(string $fieldName, ?string $language = null): mixed
            {
                return null;
            }
        };
    }
}
