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
use OpenApi\Attributes\Property;
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
final class SubGroupDetailTest extends Unit
{
    private const string IDENTIFIER = '01JBZ8QF6K3M4N5P6R7S8T9V0W';

    private const string PROPERTY_NAME = 'identifier';

    public function testIdentifierIsPublished(): void
    {
        $this->assertNull((new SubGroupDetail(7, 'Dimensions', 1))->getIdentifier());
        $this->assertSame(
            self::IDENTIFIER,
            (new SubGroupDetail(7, 'Dimensions', 1, self::IDENTIFIER))->getIdentifier()
        );

        $attributes = (new ReflectionClass(SubGroupDetail::class))->getAttributes(Schema::class);

        $this->assertSame(
            ['id', 'name', 'storeId', self::PROPERTY_NAME, 'description', 'creationDate', 'modificationDate'],
            $attributes[0]->getArguments()['required']
        );

        $arguments = $this->getIdentifierPropertyArguments(SubGroupDetail::class);

        $this->assertNotNull($arguments, 'The detail schema publishes no identifier.');
        $this->assertSame('string', $arguments['type']);
        $this->assertTrue($arguments['readOnly']);
        $this->assertTrue($arguments['nullable']);
    }

    public function testCreateAndUpdateSchemasHaveNoIdentifierProperty(): void
    {
        $this->assertNotContains(self::PROPERTY_NAME, $this->getConstructorParameterNames(SubGroupCreate::class));
        $this->assertNotContains(self::PROPERTY_NAME, $this->getConstructorParameterNames(SubGroupUpdate::class));
        $this->assertNull($this->getIdentifierPropertyArguments(SubGroupCreate::class));
        $this->assertNull($this->getIdentifierPropertyArguments(SubGroupUpdate::class));
    }

    /**
     * @param class-string $schema
     *
     * @return string[]
     */
    private function getConstructorParameterNames(string $schema): array
    {
        $constructor = (new ReflectionClass($schema))->getConstructor();

        return array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            $constructor?->getParameters() ?? []
        );
    }

    /**
     * @param class-string $schema
     *
     * @return array<string, mixed>|null
     */
    private function getIdentifierPropertyArguments(string $schema): ?array
    {
        $constructor = (new ReflectionClass($schema))->getConstructor();

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            if ($parameter->getName() !== self::PROPERTY_NAME) {
                continue;
            }

            $attributes = $parameter->getAttributes(Property::class);

            return $attributes === [] ? null : $attributes[0]->getArguments();
        }

        return null;
    }
}
