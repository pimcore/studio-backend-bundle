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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\VersionDraftElementResolver;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service\Fixture\DraftCarryingAsset;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service\Fixture\DraftCarryingObject;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service\Fixture\DraftCarryingPageSnippet;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use ReflectionClass;
use ReflectionException;

/**
 * The default the seven read paths used to inline, and that a bundle decorates.
 *
 * @internal
 */
final class VersionDraftElementResolverTest extends Unit
{
    private const int USER_ID = 7;

    /**
     * @throws Exception|ReflectionException
     */
    public function testReturnsVersionDataWhenTheUserHasADraft(): void
    {
        $draftState = $this->makeEmpty(Concrete::class);
        $element = $this->double(DraftCarryingObject::class, $this->versionHolding($draftState));

        $resolved = (new VersionDraftElementResolver())->resolve($element, $this->user());

        $this->assertSame($draftState, $resolved);
    }

    /**
     * @throws Exception|ReflectionException
     */
    public function testReturnsTheElementUnchangedWhenThereIsNoDraft(): void
    {
        $element = $this->double(DraftCarryingObject::class, null);

        $this->assertSame($element, (new VersionDraftElementResolver())->resolve($element, $this->user()));
    }

    /** Resolving another user's draft would leak their unpublished edits. */
    public function testScopesTheLookupToTheGivenUser(): void
    {
        $element = $this->double(DraftCarryingObject::class, null);

        (new VersionDraftElementResolver())->resolve($element, $this->user());

        $this->assertSame(1, $element->calls);
        $this->assertSame(self::USER_ID, $element->seenUserId);
    }

    /**
     * @throws ReflectionException
     */
    public function testPassesANullUserThroughRatherThanInventingOne(): void
    {
        $element = $this->double(DraftCarryingObject::class, null);

        (new VersionDraftElementResolver())->resolve($element, null);

        $this->assertSame(1, $element->calls);
        $this->assertNull($element->seenUserId);
    }

    /** Unversioned types must not be queried at all — seven read paths pay for it. */
    public function testNeverQueriesElementTypesThatCarryNoVersions(): void
    {
        $folder = $this->makeEmpty(Folder::class);

        $this->assertSame($folder, (new VersionDraftElementResolver())->resolve($folder, $this->user()));
    }

    /** The extraction must not narrow the supported types to Concrete. */
    public function testResolvesAssetsAndPageSnippetsToo(): void
    {
        foreach ([DraftCarryingAsset::class, DraftCarryingPageSnippet::class] as $class) {
            $draftState = $this->makeEmpty(Concrete::class);
            $element = $this->double($class, $this->versionHolding($draftState));

            $this->assertSame(
                $draftState,
                (new VersionDraftElementResolver())->resolve($element, $this->user()),
                $class . ' should resolve to its draft state'
            );
        }
    }

    /**
     * @throws Exception
     */
    private function user(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, ['getId' => self::USER_ID]);
    }

    /** Without the constructor: the models reach for the container there. */
    private function double(string $class, ?Version $version): ElementInterface
    {
        /** @var DraftCarryingAsset|DraftCarryingObject|DraftCarryingPageSnippet $element */
        $element = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        $element->stubVersion = $version;

        return $element;
    }

    /**
     * Version is final and its constructor needs the container too. Priming data also stops
     * getData() falling back to loading off storage.
     */
    private function versionHolding(mixed $data): Version
    {
        $version = (new ReflectionClass(Version::class))->newInstanceWithoutConstructor();
        $version->setData($data);

        return $version;
    }
}
