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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Metadata\Patcher;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Patcher\Adapter\CustomMetadataAdapter;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Repository\MetadataRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\DataResolverServiceInterface;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Metadata\Predefined;
use Pimcore\Model\UserInterface;

final class CustomMetadataAdapterTest extends Unit
{
    private const string SUPPORTED_TYPE = 'input';

    /**
     * An entry copied off another asset must still be appendable when its predefined
     * definition no longer exists — the submitted type is authoritative in that case.
     */
    public function testAppendsUnknownNameUsingTheSubmittedType(): void
    {
        $captured = null;

        $this->getAdapter()->patch(
            $this->getAsset([], $captured),
            ['metadata' => [
                ['name' => 'orphaned', 'language' => 'en', 'type' => self::SUPPORTED_TYPE, 'data' => 'value'],
            ]],
            $this->getUser()
        );

        $this->assertCount(1, $captured);
        $this->assertSame('orphaned', $captured[0]['name']);
        $this->assertSame(self::SUPPORTED_TYPE, $captured[0]['type']);
    }

    public function testRejectsAnUnknownNameWithAnUnsupportedType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getAdapter()->patch(
            $this->getAsset([]),
            ['metadata' => [
                ['name' => 'orphaned', 'language' => '', 'type' => 'not-a-real-type', 'data' => 'value'],
            ]],
            $this->getUser()
        );
    }

    public function testRejectsAnUnknownNameWithNoTypeAtAll(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getAdapter()->patch(
            $this->getAsset([]),
            ['metadata' => [['name' => 'orphaned', 'language' => '', 'data' => 'value']]],
            $this->getUser()
        );
    }

    /**
     * A predefined definition restricted to another asset subtype must not be appended.
     */
    public function testRejectsPredefinedMetadataRestrictedToAnotherAssetType(): void
    {
        $predefined = new Predefined();
        $predefined->setName('print_profile');
        $predefined->setType(self::SUPPORTED_TYPE);
        $predefined->setTargetSubtype('document');

        $this->expectException(InvalidArgumentException::class);

        $this->getAdapter($predefined)->patch(
            $this->getAsset([]),
            ['metadata' => [
                ['name' => 'print_profile', 'language' => '', 'type' => self::SUPPORTED_TYPE, 'data' => 'x'],
            ]],
            $this->getUser()
        );
    }

    public function testAcceptsPredefinedMetadataWithoutATargetSubtype(): void
    {
        $predefined = new Predefined();
        $predefined->setName('copyright_note');
        $predefined->setType(self::SUPPORTED_TYPE);

        $captured = null;

        $this->getAdapter($predefined)->patch(
            $this->getAsset([], $captured),
            ['metadata' => [
                ['name' => 'copyright_note', 'language' => '', 'type' => self::SUPPORTED_TYPE, 'data' => 'x'],
            ]],
            $this->getUser()
        );

        $this->assertCount(1, $captured);
    }

    /**
     * The submitted type wins on a matched entry, and the value is denormalised against it
     * rather than against the type already stored.
     */
    public function testUpdatesTheTypeOfAMatchedEntry(): void
    {
        $captured = null;

        $this->getAdapter()->patch(
            $this->getAsset(
                [['name' => 'colour_profile', 'language' => '', 'type' => 'select', 'data' => 'sRGB']],
                $captured
            ),
            ['metadata' => [
                ['name' => 'colour_profile', 'language' => '', 'type' => self::SUPPORTED_TYPE, 'data' => 'Adobe RGB'],
            ]],
            $this->getUser()
        );

        $this->assertCount(1, $captured);
        $this->assertSame(
            self::SUPPORTED_TYPE,
            $captured[0]['type'],
            'the submitted type must replace the stored one'
        );
        // denormalizeData is stubbed to return the type it was handed
        $this->assertSame(
            self::SUPPORTED_TYPE,
            $captured[0]['data'],
            'data must be denormalised against the submitted type, not the stored one'
        );
    }

    public function testRejectsAnUnsupportedTypeOnAMatchedEntry(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getAdapter()->patch(
            $this->getAsset([['name' => 'colour_profile', 'language' => '', 'type' => 'select', 'data' => 'sRGB']]),
            ['metadata' => [
                ['name' => 'colour_profile', 'language' => '', 'type' => 'not-a-real-type', 'data' => 'x'],
            ]],
            $this->getUser()
        );
    }

    /**
     * `title`, `alt` and `copyright` are seeded as `input` and published by the grid as
     * `metadata.input`; retyping one would break that contract.
     */
    public function testRejectsRetypingReservedDefaultMetadata(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getAdapter()->patch(
            $this->getAsset([['name' => 'title', 'language' => 'en', 'type' => 'input', 'data' => 'x']]),
            ['metadata' => [
                ['name' => 'title', 'language' => 'en', 'type' => 'date', 'data' => 'x'],
            ]],
            $this->getUser()
        );
    }

    public function testAllowsPatchingReservedDefaultMetadataWithItsOwnType(): void
    {
        $captured = null;

        $this->getAdapter()->patch(
            $this->getAsset(
                [['name' => 'title', 'language' => 'en', 'type' => 'input', 'data' => 'old']],
                $captured
            ),
            ['metadata' => [
                ['name' => 'title', 'language' => 'en', 'type' => 'input', 'data' => 'new'],
            ]],
            $this->getUser()
        );

        $this->assertCount(1, $captured);
        $this->assertSame('input', $captured[0]['type']);
    }

    public function testRejectsAppendingReservedDefaultMetadataWithAnotherType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getAdapter()->patch(
            $this->getAsset([]),
            ['metadata' => [['name' => 'alt', 'language' => '', 'type' => 'date', 'data' => 'x']]],
            $this->getUser()
        );
    }

    public function testLeavesUntouchedEntriesAlone(): void
    {
        $captured = null;

        $this->getAdapter()->patch(
            $this->getAsset([
                ['name' => 'title', 'language' => 'en', 'type' => 'input', 'data' => 'keep me'],
                ['name' => 'alt', 'language' => 'en', 'type' => 'input', 'data' => 'old'],
            ], $captured),
            ['metadata' => [['name' => 'alt', 'language' => 'en', 'data' => 'new']]],
            $this->getUser()
        );

        $this->assertCount(2, $captured);
        $this->assertSame('keep me', $captured[0]['data']);
    }

    private function getAdapter(?Predefined $predefined = null): CustomMetadataAdapter
    {
        return new CustomMetadataAdapter(
            $this->makeEmpty(ColumnConfigurationServiceInterface::class, [
                'getAvailableAssetColumnConfiguration' => [],
            ]),
            $this->makeEmpty(DataAdapterServiceInterface::class, [
                'supportsType' => static fn (string $type): bool => $type === self::SUPPORTED_TYPE,
            ]),
            $this->makeEmpty(DataResolverServiceInterface::class, [
                'prepareData' => static fn (array $customMetadata): array => $customMetadata,
                // records which type the value was denormalised against, so the ordering of
                // `type` before `data` in PATCHABLE_KEYS is observable in a test
                'denormalizeData' => static function (
                    array $customMetadata,
                    UserInterface $user,
                    string $adapterType
                ): string {
                    return $adapterType;
                },
            ]),
            $this->makeEmpty(MetadataRepositoryInterface::class, [
                'getPredefinedMetadataByName' => $predefined,
            ]),
        );
    }

    /**
     * The real model dispatches events on save/metadata access, which needs a booted kernel.
     * A stub keeps this a unit test: `$captured` receives whatever the adapter writes back.
     *
     * @param array<int, array<string, mixed>> $metadata
     */
    private function getAsset(array $metadata, ?array &$captured = null): Image
    {
        $asset = null;
        $asset = $this->make(Image::class, [
            'getMetadata' => $metadata,
            'getType' => 'image',
            // setMetadata is fluent, so the stub has to hand the asset back
            'setMetadata' => static function (?array $written) use (&$captured, &$asset): Image {
                $captured = $written;

                return $asset;
            },
        ]);

        return $asset;
    }

    private function getUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class);
    }
}
