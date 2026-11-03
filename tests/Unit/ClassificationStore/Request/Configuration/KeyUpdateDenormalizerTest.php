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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Request\Configuration;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Request\Configuration\KeyUpdateDenormalizer;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
final class KeyUpdateDenormalizerTest extends Unit
{
    private const string KEY_NAME = 'weight';

    private KeyUpdateDenormalizer $denormalizer;

    public function _before(): void
    {
        $this->denormalizer = new KeyUpdateDenormalizer();
    }

    public function testAnAbsentTitleIsToldApartFromATitleSentAsNull(): void
    {
        $absent = $this->denormalize([]);
        $present = $this->denormalize(['title' => null]);

        $this->assertFalse($absent->isTitleSupplied());
        $this->assertTrue($present->isTitleSupplied());
        $this->assertNull($absent->getTitle());
        $this->assertNull($present->getTitle());
    }

    public function testAnAbsentDescriptionIsToldApartFromADescriptionSentAsNull(): void
    {
        $absent = $this->denormalize([]);
        $present = $this->denormalize(['description' => null]);

        $this->assertFalse($absent->isDescriptionSupplied());
        $this->assertTrue($present->isDescriptionSupplied());
        $this->assertNull($absent->getDescription());
        $this->assertNull($present->getDescription());
    }

    public function testAnEmptyStringCountsAsSupplied(): void
    {
        $parameters = $this->denormalize(['title' => '', 'description' => '']);

        $this->assertTrue($parameters->isTitleSupplied());
        $this->assertTrue($parameters->isDescriptionSupplied());
        $this->assertSame('', $parameters->getTitle());
        $this->assertSame('', $parameters->getDescription());
    }

    public function testTheRemainingFieldsArePassedThrough(): void
    {
        $parameters = $this->denormalize([
            'title' => 'Weight',
            'description' => 'The weight',
            'type' => 'input',
            'definition' => ['fieldtype' => 'input'],
        ]);

        $this->assertSame(self::KEY_NAME, $parameters->getName());
        $this->assertSame('Weight', $parameters->getTitle());
        $this->assertSame('The weight', $parameters->getDescription());
        $this->assertSame('input', $parameters->getType());
        $this->assertSame(['fieldtype' => 'input'], $parameters->getDefinition());
    }

    public function testAMissingNameIsRejectedByTheConstructorGuard(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->denormalizer->denormalize([], KeyUpdate::class);
    }

    /**
     * @dataProvider malformedPayloadProvider
     */
    public function testAMalformedPayloadIsRejectedAsAnInvalidArgument(mixed $data): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->denormalizer->denormalize($data, KeyUpdate::class);
    }

    /**
     * A TypeError here would leave ApiExceptionSubscriber untouched and surface as a 500, so each
     * of these has to reach the client as this bundle's own 422 instead.
     *
     * @return array<string, array{0: mixed}>
     */
    public static function malformedPayloadProvider(): array
    {
        return [
            'a scalar body' => [42],
            'a string body' => ['weight'],
            'a null body' => [null],
            'a non-string name' => [['name' => 42]],
            'a non-string title' => [['name' => self::KEY_NAME, 'title' => 42]],
            'a non-string description' => [['name' => self::KEY_NAME, 'description' => ['a']]],
            'a non-string type' => [['name' => self::KEY_NAME, 'type' => false]],
            'a string definition' => [['name' => self::KEY_NAME, 'definition' => 'input']],
        ];
    }

    public function testOnlyKeyUpdateIsSupported(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], KeyUpdate::class));
        $this->assertFalse($this->denormalizer->supportsDenormalization([], self::class));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function denormalize(array $data): KeyUpdate
    {
        return $this->denormalizer->denormalize(['name' => self::KEY_NAME] + $data, KeyUpdate::class);
    }
}
