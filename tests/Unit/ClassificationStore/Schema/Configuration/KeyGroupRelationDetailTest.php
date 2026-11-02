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
