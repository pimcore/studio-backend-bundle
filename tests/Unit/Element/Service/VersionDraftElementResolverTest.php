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
use Pimcore\Bundle\StudioBackendBundle\Element\Model\ResolvedDraft;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\DraftElementResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\VersionDraftElementResolver;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service\Fixture\DraftCarryingAsset;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service\Fixture\DraftCarryingObject;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service\Fixture\DraftCarryingPageSnippet;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
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

        $this->assertSame($draftState, $resolved->getElement());
    }

    /**
     * State and identity must come from the same lookup: `draftData` names the row the UI
     * deletes by, so it may not describe a different version than the one being rendered.
     *
     * @throws Exception|ReflectionException
     */
    public function testReportsTheVersionItResolvedTheStateFrom(): void
    {
        $version = $this->versionHolding($this->makeEmpty(Concrete::class));
        $element = $this->double(DraftCarryingObject::class, $version);

        $resolved = (new VersionDraftElementResolver())->resolve($element, $this->user());

        $this->assertSame($version, $resolved->getVersion());
        $this->assertSame(1, $element->calls, 'the version must be looked up exactly once');
    }

    /**
     * @throws Exception|ReflectionException
     */
    public function testReturnsTheElementUnchangedWhenThereIsNoDraft(): void
    {
        $element = $this->double(DraftCarryingObject::class, null);

        $resolved = (new VersionDraftElementResolver())->resolve($element, $this->user());

        $this->assertSame($element, $resolved->getElement());
        $this->assertNull($resolved->getVersion());
    }

    /**
     * Version::loadData() answers null for a pruned payload or an __PHP_Incomplete_Class after
     * a class rename. Rendering published beats a TypeError on opening the element.
     *
     * @throws Exception|ReflectionException
     */
    public function testFallsBackToPublishedWhenTheVersionPayloadCannotBeRead(): void
    {
        $element = $this->double(DraftCarryingObject::class, $this->versionWithUnreadablePayload());

        $resolved = (new VersionDraftElementResolver())->resolve($element, $this->user());

        $this->assertSame($element, $resolved->getElement());
        $this->assertNull($resolved->getVersion(), 'a draft that cannot be rendered must not be advertised');
    }

    /**
     * Resolving another user's draft would leak their unpublished edits.
     *
     * @throws Exception|ReflectionException
     */
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

    /**
     * Unversioned types must not be queried at all — seven read paths pay for it.
     *
     * @throws Exception
     */
    public function testNeverQueriesElementTypesThatCarryNoVersions(): void
    {
        $folder = $this->makeEmpty(Folder::class);

        $resolved = (new VersionDraftElementResolver())->resolve($folder, $this->user());

        $this->assertSame($folder, $resolved->getElement());
        $this->assertNull($resolved->getVersion());
    }

    /**
     * The extraction must not narrow the supported types to Concrete.
     *
     * @throws Exception|ReflectionException
     */
    public function testResolvesAssetsAndPageSnippetsToo(): void
    {
        foreach ([DraftCarryingAsset::class, DraftCarryingPageSnippet::class] as $class) {
            $draftState = $this->makeEmpty(Concrete::class);
            $element = $this->double($class, $this->versionHolding($draftState));

            $this->assertSame(
                $draftState,
                (new VersionDraftElementResolver())->resolve($element, $this->user())->getElement(),
                $class . ' should resolve to its draft state'
            );
        }
    }

    /**
     * The contract a decorating bundle codes against: state may come from somewhere other than
     * the versions table, in which case there is no version row for `draftData` to name.
     *
     * @throws Exception
     */
    public function testTheContractAllowsStateWithoutAVersionIdentity(): void
    {
        $published = $this->makeEmpty(Concrete::class);
        $storedElsewhere = $this->makeEmpty(Concrete::class);

        $resolver = new class($storedElsewhere) implements DraftElementResolverInterface {
            public function __construct(private readonly ElementInterface $draft)
            {
            }

            public function resolve(ElementInterface $element, ?UserInterface $user): ResolvedDraft
            {
                return new ResolvedDraft($this->draft);
            }
        };

        $resolved = $resolver->resolve($published, $this->user());

        $this->assertSame($storedElsewhere, $resolved->getElement());
        $this->assertNull($resolved->getVersion());
    }

    /**
     * @throws Exception
     */
    private function user(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, ['getId' => self::USER_ID]);
    }

    /**
     * Without the constructor: the models reach for the container there.
     *
     * @throws ReflectionException
     */
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
     *
     * @throws ReflectionException
     */
    private function versionHolding(mixed $data): Version
    {
        $version = (new ReflectionClass(Version::class))->newInstanceWithoutConstructor();
        $version->setData($data);

        return $version;
    }

    /**
     * Unprimed, so getData() does fall through to loadData() — which bails to null when the
     * adapter has nothing to give it, exactly as it does for a pruned file or a renamed class.
     *
     * @throws Exception|ReflectionException
     */
    private function versionWithUnreadablePayload(): Version
    {
        $reflection = new ReflectionClass(Version::class);
        $version = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('storageAdapter')->setValue(
            $version,
            $this->makeEmpty(VersionStorageAdapterInterface::class)
        );

        return $version;
    }
}
