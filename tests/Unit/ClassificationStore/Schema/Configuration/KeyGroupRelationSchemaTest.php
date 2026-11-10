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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyGroupRelation;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use ReflectionClass;
use ReflectionParameter;
use function array_map;

/**
 * @internal
 */
final class KeyGroupRelationSchemaTest extends Unit
{
    private const int KEY_ID = 11;

    private const int GROUP_ID = 7;

    private const string COLUMN_DEFINITION_OVERRIDE = 'definitionOverride';

    private const string COLUMN_SUB_GROUP = 'subGroup';

    private const string COLUMN_METADATA = 'metadata';

    private const string COLUMN_LOCALIZED = 'localized';

    private const array DEFINITION_OVERRIDE = ['defaultUnit' => 'kg'];

    private const array METADATA = ['source' => 'erp'];

    /**
     * Both are published as optional and nullable, the way the sub group was added, so a relation read
     * before anything was stored on it still serialises.
     */
    public function testTheDetailPublishesTheDefinitionOverrideAndTheMetadataAsNullableAndOptional(): void
    {
        $this->assertNull($this->createDetail()->getDefinitionOverride());
        $this->assertNull($this->createDetail()->getMetadata());

        $detail = new KeyGroupRelationDetail(
            self::KEY_ID,
            self::GROUP_ID,
            0,
            false,
            definitionOverride: self::DEFINITION_OVERRIDE,
            metadata: self::METADATA
        );

        $this->assertSame(self::DEFINITION_OVERRIDE, $detail->getDefinitionOverride());
        $this->assertSame(self::METADATA, $detail->getMetadata());

        $this->assertSame(
            [
                'keyId', 'groupId', 'sorter', 'mandatory', 'keyName', 'keyDescription', 'groupName',
                self::COLUMN_SUB_GROUP, self::COLUMN_DEFINITION_OVERRIDE, self::COLUMN_METADATA,
                self::COLUMN_LOCALIZED, 'keyType', 'options', 'optionsUnavailableReason', 'units',
            ],
            $this->getConstructorParameterNames(KeyGroupRelationDetail::class)
        );
    }

    /**
     * The key's own `localized` flag joins both key-relation schemas: the per-group configuration
     * detail and the store-wide listing the object editor's add-keys modal and the grid column
     * picker read. Unlike the sub group it belongs to the key rather than to the assignment, so the
     * store-wide listing can carry it meaningfully.
     *
     * @dataProvider localizedProvider
     */
    public function testBothKeyRelationSchemasCarryTheKeysLocalizationFlag(?bool $localized): void
    {
        $detail = new KeyGroupRelationDetail(self::KEY_ID, self::GROUP_ID, 0, false, localized: $localized);
        $listed = new KeyGroupRelation(
            self::KEY_ID,
            self::GROUP_ID,
            'weight',
            'Measurements',
            null,
            null,
            $localized
        );

        $this->assertSame($localized, $detail->getLocalized());
        $this->assertSame($localized, $listed->getLocalized());
        $this->assertNull($this->createDetail()->getLocalized());
    }

    /**
     * @return array<string, array{0: ?bool}>
     */
    public static function localizedProvider(): array
    {
        return [
            'a key flagged localized' => [true],
            'a key flagged not localized' => [false],
            'a key stating nothing' => [null],
        ];
    }

    /**
     * Neither is required, matching how the sub group was added: a client reading an older relation is
     * not told to expect a value that was never stored.
     */
    public function testNeitherNewDetailPropertyIsRequired(): void
    {
        $attributes = (new ReflectionClass(KeyGroupRelationDetail::class))->getAttributes(Schema::class);

        $this->assertSame(
            ['keyId', 'groupId', 'sorter', 'mandatory', 'keyName', 'keyDescription', 'groupName'],
            $attributes[0]->getArguments()['required']
        );
    }

    /**
     * The payload mapping substitutes the constructor default for a key the body did not carry, so a
     * plain nullable default could not tell an absent property from an explicit null. All five carry a
     * presence marker instead.
     */
    public function testAnAbsentPropertyIsDistinguishableFromAnExplicitNull(): void
    {
        $absent = new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID);

        $this->assertFalse($absent->hasSorter());
        $this->assertFalse($absent->hasMandatory());
        $this->assertFalse($absent->hasDefinitionOverride());
        $this->assertFalse($absent->hasSubGroup());
        $this->assertFalse($absent->hasMetadata());

        $cleared = new KeyGroupRelationCreate(
            self::KEY_ID,
            self::GROUP_ID,
            definitionOverride: null,
            subGroup: null,
            metadata: null
        );

        $this->assertTrue($cleared->hasDefinitionOverride());
        $this->assertTrue($cleared->hasSubGroup());
        $this->assertTrue($cleared->hasMetadata());
        $this->assertNull($cleared->getDefinitionOverride());
        $this->assertNull($cleared->getSubGroup());
        $this->assertNull($cleared->getMetadata());
    }

    public function testASuppliedValueIsReportedAsSupplied(): void
    {
        $parameters = new KeyGroupRelationCreate(
            self::KEY_ID,
            self::GROUP_ID,
            5,
            true,
            self::DEFINITION_OVERRIDE,
            'dimensions',
            self::METADATA
        );

        $this->assertTrue($parameters->hasSorter());
        $this->assertTrue($parameters->hasMandatory());
        $this->assertSame(5, $parameters->getSorter());
        $this->assertTrue($parameters->isMandatory());
        $this->assertSame(self::DEFINITION_OVERRIDE, $parameters->getDefinitionOverride());
        $this->assertSame('dimensions', $parameters->getSubGroup());
        $this->assertSame(self::METADATA, $parameters->getMetadata());
    }

    /**
     * The two released properties cannot be cleared, so an explicit null on either is refused by the
     * field's own name - which is the half of the absent-versus-null rule they do not take part in.
     *
     * @dataProvider nonNullableFieldProvider
     *
     * @param array<string, mixed> $payload
     */
    public function testExplicitNullOnANonNullableFieldIsRefused(string $field, array $payload): void
    {
        try {
            new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID, ...$payload);
            $this->fail('Expected the explicit null to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString($field, $e->getMessage());
        }
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function nonNullableFieldProvider(): array
    {
        return [
            'sorter' => ['sorter', ['sorter' => null]],
            'mandatory' => ['mandatory', ['mandatory' => null]],
        ];
    }

    /**
     * Only the pair is required. `sorter` and `mandatory` were listed as required while the mapping
     * substituted a default for either anyway, and now that omitting one keeps the stored value, a
     * client the generated schema makes send them could not leave a stored value alone at all.
     */
    public function testOnlyThePairIsRequiredOnTheCreateSchema(): void
    {
        $attributes = (new ReflectionClass(KeyGroupRelationCreate::class))->getAttributes(Schema::class);

        $this->assertSame(['keyId', 'groupId'], $attributes[0]->getArguments()['required']);
    }

    public function testAValueOfTheWrongTypeIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID, sorter: 'first');
    }

    /**
     * Both `type: 'object'` members, and `get_debug_type() === 'array'` does not decide the
     * question: PHP carries a JSON list as an array too, so `{"metadata": ["erp"]}` passed that
     * read and was stored under a member the contract says is an object.
     *
     * @dataProvider objectMemberProvider
     */
    public function testAnObjectMemberSuppliedAsAJsonListIsRefused(string $member): void
    {
        try {
            new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID, ...[$member => ['erp']]);
            $this->fail('Expected the list shaped ' . $member . ' to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString($member, $e->getMessage());
        }
    }

    /**
     * `json_decode` hands `{}` and `[]` back as the same empty array, so the empty object the
     * contract allows stays accepted and still counts as supplied.
     *
     * @dataProvider objectMemberProvider
     */
    public function testAnObjectMemberSuppliedAsAnEmptyObjectIsAcceptedAndSupplied(string $member): void
    {
        $create = new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID, ...[$member => []]);

        if ($member === self::COLUMN_METADATA) {
            $this->assertTrue($create->hasMetadata());
            $this->assertSame([], $create->getMetadata());

            return;
        }

        $this->assertTrue($create->hasDefinitionOverride());
        $this->assertSame([], $create->getDefinitionOverride());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function objectMemberProvider(): array
    {
        return [
            self::COLUMN_METADATA => [self::COLUMN_METADATA],
            self::COLUMN_DEFINITION_OVERRIDE => [self::COLUMN_DEFINITION_OVERRIDE],
        ];
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

    private function createDetail(): KeyGroupRelationDetail
    {
        return new KeyGroupRelationDetail(self::KEY_ID, self::GROUP_ID, 0, false);
    }
}
