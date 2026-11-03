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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDetail;
use ReflectionClass;

/**
 * @internal
 */
final class KeyGroupRelationDetailTest extends Unit
{
    private const int KEY_ID = 11;

    private const int GROUP_ID = 7;

    public function testMetadataIsOptionalAndNullable(): void
    {
        $this->assertNull($this->createDetail()->getMetadata());
        $this->assertSame(
            ['source' => 'erp'],
            $this->createDetail(metadata: ['source' => 'erp'])->getMetadata()
        );

        $attributes = (new ReflectionClass(KeyGroupRelationDetail::class))->getAttributes(Schema::class);

        $this->assertSame(
            ['keyId', 'groupId', 'sorter', 'mandatory', 'keyName', 'keyDescription', 'groupName'],
            $attributes[0]->getArguments()['required']
        );
    }

    public function testDefinitionOverrideAndSubGroupAreOptionalAndNullable(): void
    {
        $this->assertNull($this->createDetail()->getDefinitionOverride());
        $this->assertNull($this->createDetail()->getSubGroup());

        $detail = $this->createDetail(
            definitionOverride: ['defaultUnit' => 'kg'],
            subGroup: 'Dimensions'
        );

        $this->assertSame(['defaultUnit' => 'kg'], $detail->getDefinitionOverride());
        $this->assertSame('Dimensions', $detail->getSubGroup());
    }

    /**
     * The positional signature published before the definition override and the metadata were added
     * ends with the sub group as the eighth argument. Both new fields are appended after it, so a
     * caller written against that signature keeps working and its eighth argument is still the sub
     * group rather than an array.
     */
    public function testPreviousPositionalSignatureStillConstructsTheSameRelation(): void
    {
        $detail = new KeyGroupRelationDetail(
            self::KEY_ID,
            self::GROUP_ID,
            3,
            true,
            'My Key',
            'Key description',
            'My Group',
            'Dimensions'
        );

        $this->assertSame(self::KEY_ID, $detail->getKeyId());
        $this->assertSame(self::GROUP_ID, $detail->getGroupId());
        $this->assertSame(3, $detail->getSorter());
        $this->assertTrue($detail->isMandatory());
        $this->assertSame('My Key', $detail->getKeyName());
        $this->assertSame('Key description', $detail->getKeyDescription());
        $this->assertSame('My Group', $detail->getGroupName());
        $this->assertSame('Dimensions', $detail->getSubGroup());
        $this->assertNull($detail->getDefinitionOverride());
        $this->assertNull($detail->getMetadata());
    }

    /**
     * @param array<string, mixed>|null $definitionOverride
     * @param array<string, mixed>|null $metadata
     */
    private function createDetail(
        ?array $definitionOverride = null,
        ?string $subGroup = null,
        ?array $metadata = null,
    ): KeyGroupRelationDetail {
        return new KeyGroupRelationDetail(
            self::KEY_ID,
            self::GROUP_ID,
            0,
            false,
            definitionOverride: $definitionOverride,
            subGroup: $subGroup,
            metadata: $metadata
        );
    }
}
