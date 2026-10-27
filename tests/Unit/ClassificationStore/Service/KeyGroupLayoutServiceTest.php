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
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutService;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use ReflectionClass;
use function str_contains;

/**
 * @internal
 */
final class KeyGroupLayoutServiceTest extends Unit
{
    private const int KEY_ID = 7;

    private const int GROUP_ID = 3;

    private const string KEY_NAME = 'weight';

    private const string STORED_TITLE = 'classificationstore.key.weight';

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsTheStoredTitleVerbatim(): void
    {
        $keyLayout = $this->getKeyLayout(self::STORED_TITLE);

        $this->assertSame(self::STORED_TITLE, $keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());

        foreach ((new ReflectionClass(KeyGroupLayoutService::class))->getConstructor()->getParameters() as $parameter) {
            $this->assertFalse(str_contains((string)$parameter->getType(), 'Translator'));
        }
    }

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsNullWhenNoTitleIsStored(): void
    {
        $keyLayout = $this->getKeyLayout(null);

        $this->assertNull($keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetKeyLayoutReturnsAnEmptyTitleVerbatim(): void
    {
        $keyLayout = $this->getKeyLayout('');

        $this->assertSame('', $keyLayout->getTitle());
        $this->assertSame(self::KEY_NAME, $keyLayout->getName());
    }

    /**
     * @throws Exception
     */
    private function getKeyLayout(?string $storedTitle): KeyLayout
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName(self::KEY_NAME);
        $relation->setDescription('The weight of the product');
        $relation->setDefinition('{"fieldtype":"input"}');
        $relation->setType('input');

        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->method('getByKeyGroupId')
            ->with(self::KEY_ID, self::GROUP_ID)
            ->willReturn($relation);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->method('getFieldDefinitionFromJson')
            ->willReturn(new Input());

        $keyConfig = new KeyConfig();
        if ($storedTitle !== null) {
            $keyConfig->setTitle($storedTitle);
        }

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->with(self::KEY_ID)
            ->willReturn($keyConfig);

        $service = new KeyGroupLayoutService(
            $serviceResolver,
            $repository,
            new ConcreteObjectResolver(),
            $keyConfigResolver,
        );

        return $service->getKeyLayout(new LayoutParameter('classificationStore'), self::KEY_ID, self::GROUP_ID);
    }
}
