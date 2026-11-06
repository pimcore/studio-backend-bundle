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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\PasswordAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\BlockData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Model\DataObject\ClassDefinition\Data\Password;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldCollectionData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as BrickData;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use function dirname;

/**
 * @internal
 */
final class PasswordAdapterTest extends Unit
{
    public const string STORED = '$2y$12$hvBjG/fHXnkrQXIuf2Rf4.Ev8Ln1roR3RB/zL9QOHGjbnFptgkSfi';

    public const string OTHER = '$2y$12$otherotherotherotherotOuxFoo1pNFmo0Q9bZbc6PCkqzcLzXbJe';

    private PasswordAdapter $adapter;

    private Password $field;

    private UserInterface $user;

    protected function _before(): void
    {
        $this->adapter = new PasswordAdapter();
        $this->field = new Password();
        $this->field->setName('secret');
        $this->user = $this->makeEmpty(UserInterface::class);
    }

    public function testPasswordFieldTypeIsHandledByTheDedicatedAdapter(): void
    {
        $config = Yaml::parseFile(dirname(__DIR__, 5) . '/config/pimcore/data_object_adapters.yaml');
        $mapping = $config['pimcore_studio_backend']['data_object_data_adapter_mapping'];

        $this->assertContains('password', $mapping[PasswordAdapter::class]);
        foreach ($mapping as $adapter => $types) {
            if ($adapter !== PasswordAdapter::class) {
                $this->assertNotContains('password', $types, $adapter);
            }
        }
    }

    public function testNormalizeReturnsOnlyTheMaskForAStoredValue(): void
    {
        $result = $this->adapter->normalize(self::STORED, $this->field);

        $this->assertSame('******', $result);
        $this->assertStringNotContainsString(self::STORED, (string) $result);
    }

    public function testNormalizeReturnsNullWithoutStoredValue(): void
    {
        $this->assertNull($this->adapter->normalize(null, $this->field));
        $this->assertNull($this->adapter->normalize('', $this->field));
    }

    public function testExportDoesNotContainTheStoredValue(): void
    {
        $object = new class extends Concrete {
            public function getSecret(): string
            {
                return PasswordAdapterTest::STORED;
            }
        };
        $empty = new class extends Concrete {
            public function getSecret(): ?string
            {
                return null;
            }
        };

        $this->assertSame('******', $this->adapter->getExportData($object, $this->field, 'secret'));
        $this->assertSame('', $this->adapter->getExportData($empty, $this->field, 'secret'));
    }

    public function testExportMasksTheValueOfAnObjectBrickContext(): void
    {
        $owner = $this->buildObject(null);
        $brick = new class($owner) extends BrickData {
            public function getSecret(): string
            {
                return PasswordAdapterTest::STORED;
            }
        };

        $result = $this->adapter->getExportData($owner, $this->field, 'secret', new FieldContextData(
            contextObject: $brick,
            legacyParameters: ['context' => ['containerType' => 'objectbrick', 'containerKey' => 'b', 'fieldname' => 'f']]
        ));

        $this->assertSame('******', $result);
    }

    public function testEchoedMaskKeepsTheStoredValue(): void
    {
        $object = $this->buildObject(self::STORED);

        $result = $this->adapter->getDataForSetter($object, $this->field, 'secret', ['secret' => '******'], $this->user);

        $this->assertSame(self::STORED, $result);
    }

    public function testEchoedUiPlaceholderKeepsTheStoredValue(): void
    {
        $object = $this->buildObject(self::STORED);

        $result = $this->adapter->getDataForSetter($object, $this->field, 'secret', ['secret' => '********'], $this->user);

        $this->assertSame(self::STORED, $result);
    }

    public function testEchoedMaskNeverPersistsTheMaskNorClearsWithoutStoredValue(): void
    {
        $object = $this->buildObject(null);

        $result = $this->adapter->getDataForSetter($object, $this->field, 'secret', ['secret' => '******'], $this->user);

        $this->assertNull($result);
    }

    public function testNewPasswordIsPassedThrough(): void
    {
        $object = $this->buildObject(self::STORED);

        $result = $this->adapter->getDataForSetter(
            $object,
            $this->field,
            'secret',
            ['secret' => 'a-brand-new-password'],
            $this->user
        );

        $this->assertSame('a-brand-new-password', $result);
    }

    public function testEmptyValueClearsThePassword(): void
    {
        $object = $this->buildObject(self::STORED);

        $this->assertNull($this->adapter->getDataForSetter($object, $this->field, 'secret', ['secret' => ''], $this->user));
    }

    public function testEchoedMaskInsideObjectBrickKeepsTheStoredValue(): void
    {
        $owner = $this->buildObject(self::OTHER);
        $brick = new class($owner) extends BrickData {
            public function getSecret(): string
            {
                return PasswordAdapterTest::STORED;
            }
        };

        $result = $this->adapter->getDataForSetter(
            $owner,
            $this->field,
            'secret',
            ['secret' => '******'],
            $this->user,
            new FieldContextData($brick)
        );

        $this->assertSame(self::STORED, $result);
    }

    public function testEchoedMaskInsideBlockKeepsTheStoredValue(): void
    {
        $blockData = new BlockData(
            ['secret' => new BlockElement('secret', 'password', self::STORED)],
            ['containerType' => 'block', 'classId' => 'x', 'fieldname' => 'blocks']
        );

        $result = $this->adapter->getDataForSetter(
            $this->buildObject(self::OTHER),
            $this->field,
            'secret',
            ['secret' => '******'],
            $this->user,
            new FieldContextData($blockData)
        );

        $this->assertSame(self::STORED, $result);
    }

    public function testEchoedMaskOfLocalizedFieldInsideBlockKeepsTheValueOfThatLanguage(): void
    {
        $localized = (new Localizedfield())->setItems([
            'de' => ['secret' => self::STORED],
            'en' => ['secret' => self::OTHER],
        ]);
        $blockData = new BlockData(
            ['localizedfields' => new BlockElement('localizedfields', 'localizedfields', $localized)],
            ['containerType' => 'block', 'classId' => 'x', 'fieldname' => 'blocks']
        );

        $result = $this->adapter->getDataForSetter(
            $this->buildObject(null),
            $this->field,
            'secret',
            ['secret' => '******'],
            $this->user,
            new FieldContextData($blockData, 'de')
        );

        $this->assertSame(self::STORED, $result);
    }

    public function testEchoedMaskInsideBlockOfRebuiltContainerIsRejected(): void
    {
        $blockData = new BlockData(
            [],
            ['containerType' => 'block', 'classId' => 'x', 'fieldname' => 'blocks', 'inRebuiltContainer' => true]
        );

        foreach (['******', '********'] as $placeholder) {
            try {
                $this->adapter->getDataForSetter(
                    $this->buildObject(self::OTHER),
                    $this->field,
                    'secret',
                    ['secret' => $placeholder],
                    $this->user,
                    new FieldContextData($blockData)
                );
                $this->fail('The placeholder must be rejected');
            } catch (ValidationException $exception) {
                $this->assertStringNotContainsString($placeholder, $exception->getMessage());
                $this->assertStringNotContainsString(self::OTHER, $exception->getMessage());
            }
        }
    }

    public function testEchoedMaskIsRejectedWhenTheStoredValueCannotBeRead(): void
    {
        $object = new class extends Concrete {
            public function get(string $fieldName, ?string $language = null): mixed
            {
                throw new RuntimeException('not readable');
            }
        };

        $this->expectException(ValidationException::class);

        $this->adapter->getDataForSetter($object, $this->field, 'secret', ['secret' => '******'], $this->user);
    }

    public function testEchoedMaskForLocalizedContextWithoutLanguageIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->adapter->getDataForSetter(
            $this->buildObject(null),
            $this->field,
            'secret',
            ['secret' => '******'],
            $this->user,
            new FieldContextData(new Localizedfield())
        );
    }

    public function testEchoedMaskInsideNewBlockItemStoresNothing(): void
    {
        $blockData = new BlockData([], ['containerType' => 'block', 'classId' => 'x', 'fieldname' => 'blocks']);

        $result = $this->adapter->getDataForSetter(
            $this->buildObject(self::OTHER),
            $this->field,
            'secret',
            ['secret' => '******'],
            $this->user,
            new FieldContextData($blockData)
        );

        $this->assertNull($result);
    }

    public function testEchoedMaskOfEncryptedPasswordKeepsTheStoredValue(): void
    {
        $wrapped = new EncryptedField($this->field, self::STORED);

        $topLevel = $this->adapter->getDataForSetter(
            $this->buildObjectWithValue($wrapped),
            $this->field,
            'secret',
            ['secret' => '******'],
            $this->user
        );
        $blockData = new BlockData(
            ['secret' => new BlockElement('secret', 'encryptedField', $wrapped)],
            ['containerType' => 'block', 'classId' => 'x', 'fieldname' => 'blocks']
        );
        $inBlock = $this->adapter->getDataForSetter(
            $this->buildObject(null),
            $this->field,
            'secret',
            ['secret' => '******'],
            $this->user,
            new FieldContextData($blockData)
        );

        $this->assertSame(self::STORED, $topLevel);
        $this->assertSame(self::STORED, $inBlock);
        $this->assertSame('******', $this->adapter->normalize($wrapped, $this->field));
    }

    public function testEchoedMaskInsideLocalizedFieldKeepsTheValueOfThatLanguage(): void
    {
        $object = new class extends Concrete {
            public function get(string $fieldName, ?string $language = null): mixed
            {
                return $language === 'de' ? PasswordAdapterTest::STORED : PasswordAdapterTest::OTHER;
            }
        };

        $result = $this->adapter->getDataForSetter(
            $object,
            $this->field,
            'secret',
            ['secret' => '******'],
            $this->user,
            new FieldContextData(null, 'de')
        );

        $this->assertSame(self::STORED, $result);
    }

    public function testEchoedMaskInsideFieldCollectionKeepsTheStoredItemValue(): void
    {
        $stored = $this->buildCollectionItem('item', 0, self::STORED);
        $object = $this->buildCollectionOwner([$stored, $this->buildCollectionItem('item', 1, self::OTHER)]);
        // the API rebuilds the item without values
        $rebuilt = $this->buildCollectionItem('item', 1, null);

        $result = $this->adapter->getDataForSetter(
            $object,
            $this->field,
            'secret',
            ['secret' => '******'],
            $this->user,
            new FieldContextData($rebuilt)
        );

        $this->assertSame(self::OTHER, $result);
    }

    public function testEchoedMaskInsideNewOrDifferentFieldCollectionItemStoresNothing(): void
    {
        $object = $this->buildCollectionOwner([$this->buildCollectionItem('item', 0, self::STORED)]);

        foreach ([$this->buildCollectionItem('item', 5, null), $this->buildCollectionItem('other', 0, null)] as $rebuilt) {
            $result = $this->adapter->getDataForSetter(
                $object,
                $this->field,
                'secret',
                ['secret' => '******'],
                $this->user,
                new FieldContextData($rebuilt)
            );

            $this->assertNull($result);
        }
    }

    private function buildObjectWithValue(mixed $stored): Concrete
    {
        return new class($stored) extends Concrete {
            public function __construct(private readonly mixed $stored)
            {
            }

            public function get(string $fieldName, ?string $language = null): mixed
            {
                return $this->stored;
            }
        };
    }

    private function buildObject(?string $stored): Concrete
    {
        return new class($stored) extends Concrete {
            public function __construct(private readonly ?string $stored)
            {
            }

            public function get(string $fieldName, ?string $language = null): mixed
            {
                return $this->stored;
            }
        };
    }

    private function buildCollectionItem(string $type, int $index, ?string $stored): FieldCollectionData
    {
        $item = new class($type, $stored) extends FieldCollectionData {
            public function __construct(string $type, private readonly ?string $stored)
            {
                $this->type = $type;
            }

            public function get(string $fieldName, ?string $language = null): mixed
            {
                return $this->stored;
            }
        };
        $item->setIndex($index);
        $item->setFieldname('collection');

        return $item;
    }

    /**
     * @param FieldCollectionData[] $items
     */
    private function buildCollectionOwner(array $items): Concrete
    {
        return new class($items) extends Concrete {
            public function __construct(private readonly array $items)
            {
            }

            public function get(string $fieldName, ?string $language = null): mixed
            {
                return new Fieldcollection($this->items, 'collection');
            }
        };
    }
}
