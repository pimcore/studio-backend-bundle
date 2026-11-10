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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Schema\Configuration;

use Codeception\Test\Unit;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupUpdate;
use ReflectionClass;
use ReflectionParameter;
use function array_map;

/**
 * @internal
 */
final class SubGroupSchemaTest extends Unit
{
    private const string IDENTIFIER = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    private const string SUB_GROUP_NAME = 'Dimensions';

    public function testDetailPublishesTheVocabularyEntryWithAReadOnlyIdentifier(): void
    {
        $detail = new SubGroupDetail(
            42,
            self::SUB_GROUP_NAME,
            1,
            'Physical dimensions',
            1734567890,
            1734567891,
            self::IDENTIFIER
        );

        $this->assertSame(42, $detail->getId());
        $this->assertSame(self::SUB_GROUP_NAME, $detail->getName());
        $this->assertSame(1, $detail->getStoreId());
        $this->assertSame('Physical dimensions', $detail->getDescription());
        $this->assertSame(1734567890, $detail->getCreationDate());
        $this->assertSame(1734567891, $detail->getModificationDate());
        $this->assertSame(self::IDENTIFIER, $detail->getIdentifier());

        // nullable, so an entry read before its identifier was generated still serialises
        $this->assertNull((new SubGroupDetail(42, self::SUB_GROUP_NAME, 1))->getIdentifier());

        $this->assertSame(
            [
                'id', 'name', 'storeId', 'description', 'creationDate', 'modificationDate', 'identifier',
                'customMetadata',
            ],
            $this->getConstructorParameterNames(SubGroupDetail::class)
        );

        $attributes = (new ReflectionClass(SubGroupDetail::class))->getAttributes(Schema::class);

        $this->assertSame(
            ['id', 'name', 'storeId', 'description', 'creationDate', 'modificationDate'],
            $attributes[0]->getArguments()['required']
        );
    }

    public function testCreateTakesNameAndStoreIdOnly(): void
    {
        $this->assertSame(['name', 'storeId'], $this->getConstructorParameterNames(SubGroupCreate::class));

        $attributes = (new ReflectionClass(SubGroupCreate::class))->getAttributes(Schema::class);

        $this->assertSame(['name', 'storeId'], $attributes[0]->getArguments()['required']);
    }

    public function testUpdateTakesNameDescriptionAndTheOptionalPairsOnly(): void
    {
        $this->assertSame(
            ['name', 'description', 'customMetadata'],
            $this->getConstructorParameterNames(SubGroupUpdate::class)
        );

        $attributes = (new ReflectionClass(SubGroupUpdate::class))->getAttributes(Schema::class);

        $this->assertSame(['name', 'description'], $attributes[0]->getArguments()['required']);
    }

    /**
     * The identifier is generated, never accepted: a client cannot pick one on either write path.
     */
    public function testNeitherWriteSchemaAcceptsAnIdentifier(): void
    {
        $this->assertNotContains('identifier', $this->getConstructorParameterNames(SubGroupCreate::class));
        $this->assertNotContains('identifier', $this->getConstructorParameterNames(SubGroupUpdate::class));
    }

    /**
     * @return string[]
     */
    private function getConstructorParameterNames(string $class): array
    {
        return array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            (new ReflectionClass($class))->getConstructor()->getParameters()
        );
    }
}
