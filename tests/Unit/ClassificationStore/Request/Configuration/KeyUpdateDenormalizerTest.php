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
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;

/**
 * @internal
 */
final class KeyUpdateDenormalizerTest extends Unit
{
    private const string KEY_NAME = 'weight';

    private const string IDENTIFIER = '01K9T0ZQ0VX8W4S1R7J3A2B5C6';

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

    /**
     * The flag is a tri-state, so the payload has three answers and the denormalizer is the only
     * place the third - an omitted member - is still visible.
     *
     * @dataProvider localizedPayloadProvider
     *
     * @param array<string, mixed> $payload
     */
    public function testTheLocalizationFlagIsReadAsATriState(
        array $payload,
        bool $supplied,
        ?bool $expected
    ): void {
        $parameters = $this->denormalize($payload);

        $this->assertSame($supplied, $parameters->isLocalizedSupplied());
        $this->assertSame($expected, $parameters->getLocalized());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: bool, 2: ?bool}>
     */
    public static function localizedPayloadProvider(): array
    {
        return [
            'an absent member is an omission' => [[], false, null],
            'a member sent as null is supplied' => [['localized' => null], true, null],
            'a member sent as true is supplied' => [['localized' => true], true, true],
            'a member sent as false is supplied' => [['localized' => false], true, false],
        ];
    }

    /**
     * A wrongly shaped flag is refused here, before the request reaches the service that would
     * write the key - so the refusal is this bundle's own 422 rather than the 500 a TypeError in
     * the repository would become, and nothing is written on the way to it.
     */
    public function testANonBooleanLocalizationFlagIsRefusedAsUnprocessable(): void
    {
        try {
            $this->denormalize(['localized' => 'true']);
            $this->fail('Expected the non-boolean localization flag to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(HttpResponseCodes::UNPROCESSABLE_CONTENT->value, $e->getStatusCode());
            $this->assertStringContainsString('localized', $e->getMessage());
        }
    }

    public function testAnAbsentCustomMetadataIsToldApartFromOneSentAsNull(): void
    {
        $absent = $this->denormalize([]);
        $null = $this->denormalize(['customMetadata' => null]);
        $pairs = $this->denormalize(['customMetadata' => ['indexed' => 'yes', 'title.de' => 'Gewicht']]);

        $this->assertFalse($absent->isCustomMetadataSupplied());
        $this->assertTrue($null->isCustomMetadataSupplied());
        $this->assertNull($null->getCustomMetadata());
        $this->assertTrue($pairs->isCustomMetadataSupplied());
        $this->assertSame(['indexed' => 'yes', 'title.de' => 'Gewicht'], $pairs->getCustomMetadata());
    }

    public function testACustomMetadataThatIsNotAnObjectIsRefusedAsUnprocessable(): void
    {
        try {
            $this->denormalize(['customMetadata' => 5]);
            $this->fail('Expected the non-object customMetadata to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(HttpResponseCodes::UNPROCESSABLE_CONTENT->value, $e->getStatusCode());
            $this->assertStringContainsString('customMetadata', $e->getMessage());
        }
    }

    /**
     * The member's contract is an `object`, and a JSON list is not one. It reaches the denormalizer
     * as a PHP array, so `is_array()` alone would have let `{"customMetadata": ["erp"]}` through to
     * the write and stored a list under a member every reader takes for a map of pairs.
     */
    public function testACustomMetadataSentAsAJsonListIsRefusedAsUnprocessable(): void
    {
        foreach ([['erp'], ['erp', 'pim'], [0 => 'erp', 1 => 'pim']] as $list) {
            try {
                $this->denormalize(['customMetadata' => $list]);
                $this->fail('Expected the list shaped customMetadata to be refused.');
            } catch (InvalidArgumentException $e) {
                $this->assertSame(HttpResponseCodes::UNPROCESSABLE_CONTENT->value, $e->getStatusCode());
                $this->assertStringContainsString('customMetadata', $e->getMessage());
            }
        }
    }

    /**
     * The other side of that refusal: `json_decode('{}', true)` and `json_decode('[]', true)` are
     * the same empty array, so the empty object the contract allows has to stay accepted - and it
     * counts as supplied, which is how a client empties the pairs without clearing the column.
     */
    public function testAnEmptyCustomMetadataObjectIsAcceptedAndSupplied(): void
    {
        $empty = $this->denormalize(['customMetadata' => []]);

        $this->assertTrue($empty->isCustomMetadataSupplied());
        $this->assertSame([], $empty->getCustomMetadata());
    }

    /**
     * A list *inside* the object is a value like any other: only the member itself has to be an
     * object, and nothing about what it carries is restricted.
     */
    public function testAListNestedInsideTheCustomMetadataIsKept(): void
    {
        $pairs = ['units' => ['kg', 'g'], 'source' => 'erp'];

        $this->assertSame($pairs, $this->denormalize(['customMetadata' => $pairs])->getCustomMetadata());
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
            'a string localized flag' => [['name' => self::KEY_NAME, 'localized' => 'true']],
            'a numeric localized flag' => [['name' => self::KEY_NAME, 'localized' => 1]],
            'an array localized flag' => [['name' => self::KEY_NAME, 'localized' => []]],
        ];
    }

    /**
     * The key-update request path runs through this denormalizer rather than through Symfony's own
     * payload mapping, so the `identifier` key only reaches the schema's refusal because
     * {@see KeyUpdateDenormalizer::denormalize()} forwards it. Without this case, dropping that
     * forwarding would silently start accepting a supplied identifier on the live endpoint while
     * every test still passed - the schema test constructs the DTO directly and never crosses here.
     *
     * @dataProvider suppliedIdentifierProvider
     */
    public function testASuppliedIdentifierIsRefusedThroughTheDenormalizer(mixed $identifier): void
    {
        try {
            $this->denormalize(['identifier' => $identifier]);
            $this->fail('Expected the supplied identifier to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(HttpResponseCodes::UNPROCESSABLE_CONTENT->value, $e->getStatusCode());
            $this->assertStringContainsString('Identifier', $e->getMessage());
        }
    }

    /**
     * An explicit null is a supplied value, not an omission: the request asked to set the identifier
     * and has to be told that it cannot, so it is refused exactly like a value.
     *
     * @return array<string, array{0: mixed}>
     */
    public static function suppliedIdentifierProvider(): array
    {
        return [
            'a supplied identifier' => [self::IDENTIFIER],
            'an explicitly null identifier' => [null],
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
