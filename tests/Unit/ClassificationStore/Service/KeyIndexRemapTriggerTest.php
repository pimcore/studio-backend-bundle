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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Message\UpdateClassMappingMessage;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\SettingsStoreResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\KeyService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Messenger\SearchIndexRemapHandler;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Messenger\SearchIndexRemapMessage;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexFlagResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapQueue;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTargetResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTrigger;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyContext;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyDiagnostics;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyRefusalsInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryWriter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactKeyEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactRelationWriter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactTargetMatcher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\Tool\SettingsStore;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use function json_encode;
use function sprintf;

/**
 * A key save that changes the key's resolved search index flag has to hand one remap over per search
 * index its store reaches, on the key update endpoint and on the configuration apply alike, and a
 * save that changes nothing has to hand nothing over at all - a remap is a whole class re-indexed.
 *
 * The remap is queued, never run here: what the write path does is dispatch, and the mapping is
 * rewritten by whoever drains the queue.
 *
 * The queue is the real one throughout, over an in-memory stand-in for the settings store it keeps
 * its "already queued" marker in, so that the coalescing is observed where it happens rather than
 * on a double that was told to report it.
 *
 * @internal
 */
final class KeyIndexRemapTriggerTest extends Unit
{
    private const int STORE_ID = 1;

    private const int OTHER_STORE_ID = 2;

    private const int KEY_ID = 51;

    private const string KEY_NAME = 'weight';

    private const string KEY_IDENTIFIER = '01KEY';

    private const string GROUP_IDENTIFIER = '01GROUP';

    private const string CLASS_ID = 'CAR';

    private const string OTHER_CLASS_ID = 'BIKE';

    private const string INDEXED = '{"fieldtype":"input","indexed":true}';

    private const string NOT_INDEXED = '{"fieldtype":"input","indexed":false}';

    private const string NO_MEMBER = '{"fieldtype":"input"}';

    /**
     * @var list<Envelope> the messages handed to the bus, in order, with their stamps
     */
    private array $dispatched = [];

    /**
     * @var array<string, array<string, true>> the settings store, keyed by scope and by id
     */
    private array $settings = [];

    /**
     * How often a store was resolved to the indices it reaches, which loads every class definition
     * and is not something a save that changed no flag may pay for.
     */
    private int $targetLookups = 0;

    public function _before(): void
    {
        $this->dispatched = [];
        $this->settings = [];
        $this->targetLookups = 0;
    }

    /**
     * `PUT /classification-store/configuration/keys/{id}`: the stored definition indexes the key,
     * the one the payload carries excludes it, so the store's index is remapped.
     */
    public function testKeyUpdateThatFlipsTheFlagEnqueuesARemap(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $this->createKeyService($trigger, self::INDEXED)->updateKey(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, definition: ['fieldtype' => 'input', 'indexed' => false])
        );

        $this->assertSame([self::CLASS_ID], $this->getRemappedClassIds());
    }

    /**
     * The configuration apply, which reaches the same key write from the import endpoint and from
     * the console command. The whole apply is one hand-over, which is why the writer notes and the
     * transfer service flushes.
     */
    public function testApplyThatFlipsTheFlagEnqueuesARemap(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);
        $writer = $this->createEntryWriter($trigger);

        $writer->writeKey(
            $this->createApplyContext(),
            $this->createStoredKey(self::INDEXED),
            $this->createKeyEntry(['indexed' => false])
        );
        $trigger->flush();

        $this->assertSame([self::CLASS_ID], $this->getRemappedClassIds());
    }

    /**
     * Two keys of the same store flipping inside one apply is still one remap of that store's index,
     * and an apply that writes thousands of keys is one hand-over rather than thousands.
     */
    public function testAnApplyFlippingTwoKeysOfOneStoreHandsOneRemapOver(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);
        $writer = $this->createEntryWriter($trigger);
        $context = $this->createApplyContext();

        $writer->writeKey($context, $this->createStoredKey(self::INDEXED), $this->createKeyEntry(['indexed' => false]));
        $writer->writeKey($context, $this->createStoredKey(self::NOT_INDEXED), $this->createKeyEntry([]));
        $trigger->flush();

        $this->assertSame([self::CLASS_ID], $this->getRemappedClassIds());
    }

    /**
     * A dry run writes no key row, so it notes nothing and hands nothing over: there is no change to
     * converge to.
     */
    public function testADryRunApplyEnqueuesNothing(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);
        $writer = $this->createEntryWriter($trigger);

        $writer->writeKey(
            new ApplyContext(true, new ApplyReport()),
            $this->createStoredKey(self::INDEXED),
            $this->createKeyEntry(['indexed' => false])
        );
        $trigger->flush();

        $this->assertSame([], $this->getRemappedClassIds());
    }

    /**
     * The index is per data object class while the store is not, so a store two classes carry a
     * field of is two indices and two remaps - and a class two stores reach is still remapped once.
     */
    public function testEveryIndexTheNotedStoresReachIsRemappedExactlyOnce(): void
    {
        $trigger = $this->createTrigger([
            self::STORE_ID => [self::CLASS_ID, self::OTHER_CLASS_ID],
            self::OTHER_STORE_ID => [self::OTHER_CLASS_ID],
        ]);

        $trigger->noteFlagChange(self::STORE_ID, self::INDEXED, self::NOT_INDEXED);
        $trigger->noteFlagChange(self::OTHER_STORE_ID, self::INDEXED, self::NOT_INDEXED);
        $trigger->flush();

        $this->assertSame([self::CLASS_ID, self::OTHER_CLASS_ID], $this->getRemappedClassIds());
    }

    /**
     * What the write path does is hand the remap over; the mapping is rewritten and the elements
     * re-indexed by whoever drains the queue. The message carries a delay so the worker picks it up
     * after the writing request has committed.
     */
    public function testTheRemapIsHandedToTheQueueAndNotRunInline(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $trigger->noteFlagChange(self::STORE_ID, self::INDEXED, self::NOT_INDEXED);
        $trigger->flush();

        $this->assertCount(1, $this->dispatched);
        $this->assertSame([self::CLASS_ID], $this->getRemappedClassIds());
        $this->assertSame([self::CLASS_ID], $this->getDelayedClassIds());
    }

    /**
     * And nothing on the way there asks for synchronous index processing, which is the one thing
     * that would turn the hand-over into a rebuild inside the request
     * ({@see \Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveService} is where that
     * is asked for, deliberately).
     */
    public function testTheRemapPathNeverAsksForSynchronousProcessing(): void
    {
        $services = [SearchIndexRemapTrigger::class, SearchIndexRemapQueue::class, SearchIndexRemapHandler::class];

        foreach ($services as $service) {
            foreach ($this->readConstructorTypes($service) as $type) {
                $this->assertNotSame(
                    SynchronousProcessingServiceInterface::class,
                    $type,
                    sprintf('%s must not reach for synchronous index processing.', $service)
                );
            }
        }
    }

    /**
     * Twenty flag changes of the same store, each its own request, while the remap of the first is
     * still queued and unprocessed: the marker the first hand-over left behind answers for them, so
     * the nineteen after it add nothing.
     */
    public function testTwentyFlagChangesLeaveOneQueuedRemap(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        for ($save = 0; $save < 20; $save++) {
            $trigger->noteFlagChange(
                self::STORE_ID,
                $save % 2 === 0 ? self::INDEXED : self::NOT_INDEXED,
                $save % 2 === 0 ? self::NOT_INDEXED : self::INDEXED
            );
            $trigger->flush();
        }

        $this->assertSame([self::CLASS_ID], $this->getRemappedClassIds());
    }

    /**
     * The queued remap stops being pending when it is handled, and handing it on is the handler's
     * other half: the mapping update a class save hands over is what actually rewrites the mapping,
     * so a flag change after it is a hand-over of its own rather than one dropped forever.
     */
    public function testHandlingTheQueuedRemapReleasesItAndHandsTheMappingUpdateOn(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $trigger->noteFlagChange(self::STORE_ID, self::INDEXED, self::NOT_INDEXED);
        $trigger->flush();
        $this->handleQueuedRemaps();

        $trigger->noteFlagChange(self::STORE_ID, self::NOT_INDEXED, self::INDEXED);
        $trigger->flush();

        $this->assertSame([self::CLASS_ID, self::CLASS_ID], $this->getRemappedClassIds());
        $this->assertSame([self::CLASS_ID], $this->getMappingUpdateClassIds());
    }

    /**
     * And it does not even ask which indices the store reaches, which is a read of every class
     * definition there is.
     *
     * @dataProvider unchangedFlagProvider
     */
    public function testASaveThatDoesNotChangeTheFlagEnqueuesNothing(?string $before, ?string $after): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $trigger->noteFlagChange(self::STORE_ID, $before, $after);
        $trigger->flush();

        $this->assertSame([], $this->getRemappedClassIds());
        $this->assertSame(0, $this->targetLookups);
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function unchangedFlagProvider(): array
    {
        return [
            'absent before and absent after' => [self::NO_MEMBER, self::NO_MEMBER],
            'true before and absent after' => [self::INDEXED, self::NO_MEMBER],
            'absent before and true after' => [self::NO_MEMBER, self::INDEXED],
            'false before and false after' => [self::NOT_INDEXED, self::NOT_INDEXED],
            'a non boolean before and true after' => ['{"indexed":"no"}', self::INDEXED],
            'a definition that does not parse before an absent member' => ['not json', self::NO_MEMBER],
        ];
    }

    /**
     * A title only update carries no definition at all, so the flag cannot have moved and the key is
     * not even read for it.
     *
     * @dataProvider metadataOnlyUpdateProvider
     */
    public function testATitleOrDescriptionOnlyUpdateEnqueuesNothing(KeyUpdate $parameters): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $this->createKeyService($trigger, self::NOT_INDEXED)->updateKey(self::KEY_ID, $parameters);

        $this->assertSame([], $this->getRemappedClassIds());
    }

    /**
     * @return array<string, array{0: KeyUpdate}>
     */
    public static function metadataOnlyUpdateProvider(): array
    {
        return [
            'a title only update' => [
                new KeyUpdate(name: self::KEY_NAME, title: 'Mass', titleSupplied: true),
            ],
            'a description only update' => [
                new KeyUpdate(name: self::KEY_NAME, description: 'The mass', descriptionSupplied: true),
            ],
        ];
    }

    /**
     * A key create builds a definition that cannot carry the member, so a created key is indexed and
     * adds nothing to any mapping until it is assigned to a group.
     */
    public function testAKeyCreateEnqueuesNothing(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $this->createKeyService($trigger, self::NO_MEMBER)->createKey(
            new KeyCreate(self::KEY_NAME, self::STORE_ID)
        );

        $this->assertSame([], $this->getRemappedClassIds());
    }

    /**
     * An assignment write is not a key save: the flag is the key's and an assignment overrides
     * nothing, so writing one - including one whose `metadata` names `indexed` - changes nothing
     * about the search index.
     */
    public function testAnAssignmentWriteEnqueuesNoRemap(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);
        $context = $this->createApplyContext();
        $context->register(ArtifactSection::GROUPS, self::GROUP_IDENTIFIER, 41);
        $context->register(ArtifactSection::KEYS, self::KEY_IDENTIFIER, self::KEY_ID);

        $this->createEntryWriter($trigger)->writeKeyAssignment(
            $context,
            null,
            [
                'group' => self::GROUP_IDENTIFIER,
                'key' => self::KEY_IDENTIFIER,
                'sorter' => 1,
                'mandatory' => false,
                'metadata' => ['source' => 'erp', 'indexed' => false],
            ],
            self::GROUP_IDENTIFIER . '->' . self::KEY_IDENTIFIER
        );
        $trigger->flush();

        $this->assertSame([], $this->getRemappedClassIds());
    }

    /**
     * @param array<int, list<string>> $targetsByStore the class ids each store reaches
     */
    private function createTrigger(array $targetsByStore): SearchIndexRemapTrigger
    {
        return new SearchIndexRemapTrigger(
            new SearchIndexFlagResolver(),
            $this->makeEmpty(SearchIndexRemapTargetResolverInterface::class, [
                'resolveForStore' => function (int $storeId) use ($targetsByStore): array {
                    $this->targetLookups++;

                    return $targetsByStore[$storeId] ?? [];
                },
            ]),
            new SearchIndexRemapQueue($this->createMessageBus(), $this->createSettingsStoreResolver()),
            $this->makeEmpty(LoggerInterface::class)
        );
    }

    /**
     * Drains the queued remaps the way the worker does, which is what releases their marker.
     */
    private function handleQueuedRemaps(): void
    {
        $handler = new SearchIndexRemapHandler(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => fn (string $id): ClassDefinition => $this->createClassDefinition($id),
            ]),
            $this->createMessageBus(),
            new SearchIndexRemapQueue($this->createMessageBus(), $this->createSettingsStoreResolver())
        );

        $queued = [];

        foreach ($this->dispatched as $envelope) {
            $message = $envelope->getMessage();

            if ($message instanceof SearchIndexRemapMessage) {
                $queued[] = $message;
            }
        }

        foreach ($queued as $message) {
            $handler($message);
        }
    }

    private function createKeyService(SearchIndexRemapTrigger $trigger, string $storedDefinition): KeyService
    {
        $stored = $this->createStoredKey($storedDefinition);

        return new KeyService(
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(FilterMapperServiceInterface::class),
            $this->makeEmpty(KeyRelationResolverInterface::class),
            $this->makeEmpty(KeyRepositoryInterface::class, [
                'getById' => $stored,
                'create' => $stored,
                'update' => static function (int $id, KeyUpdate $parameters) use ($stored): KeyConfig {
                    $definition = $parameters->getDefinition();

                    if ($definition !== null) {
                        $stored->setDefinition((string) json_encode($definition));
                    }

                    return $stored;
                },
            ]),
            $this->makeEmpty(KeyHydratorInterface::class, [
                'hydrateKeyDetail' => new KeyDetail(self::KEY_ID, self::KEY_NAME, self::STORE_ID, 'input', true),
            ]),
            $trigger
        );
    }

    private function createEntryWriter(SearchIndexRemapTrigger $trigger): ArtifactEntryWriter
    {
        $parser = new ArtifactParser();
        $matcher = new ArtifactTargetMatcher();
        $diagnostics = new ApplyDiagnostics();
        $refusals = $this->makeEmpty(ApplyRefusalsInterface::class);
        $keyRepository = $this->makeEmpty(KeyRepositoryInterface::class, [
            'saveFromArtifact' => fn (?KeyConfig $config, int $storeId, ArtifactKeyEntry $entry): KeyConfig
                => $this->createSavedKey($entry->getDefinition()),
        ]);

        return new ArtifactEntryWriter(
            $parser,
            $matcher,
            $diagnostics,
            $refusals,
            new ArtifactRelationWriter(
                $parser,
                $matcher,
                $diagnostics,
                $refusals,
                $this->makeEmpty(CollRelationRepositoryInterface::class),
                $this->makeEmpty(KeyGroupRelationRepositoryInterface::class, [
                    'createOrUpdateFromArtifact' => new KeyGroupRelation(),
                ])
            ),
            $keyRepository,
            $trigger
        );
    }

    private function createApplyContext(): ApplyContext
    {
        $context = new ApplyContext(false, new ApplyReport());
        $context->setStoreId(self::STORE_ID);

        return $context;
    }

    /**
     * The key row as the store carries it before the save.
     */
    private function createStoredKey(string $definition): KeyConfig
    {
        $config = new KeyConfig();
        $config->setId(self::KEY_ID);
        $config->setStoreId(self::STORE_ID);
        $config->setIdentifier(self::KEY_IDENTIFIER);
        $config->setName(self::KEY_NAME);
        $config->setType('input');
        $config->setEnabled(true);
        $config->setDefinition($definition);

        return $config;
    }

    /**
     * The key row the apply's save left behind, which carries the file's definition.
     *
     * @param array<string, mixed>|null $definition
     */
    private function createSavedKey(?array $definition): KeyConfig
    {
        return $this->createStoredKey((string) json_encode($definition ?? []));
    }

    /**
     * @param array<string, mixed> $definitionMembers
     *
     * @return array<string, mixed>
     */
    private function createKeyEntry(array $definitionMembers): array
    {
        return [
            'identifier' => self::KEY_IDENTIFIER,
            'name' => self::KEY_NAME,
            'title' => null,
            'description' => null,
            'type' => 'input',
            'definition' => ['fieldtype' => 'input'] + $definitionMembers,
            'enabled' => true,
        ];
    }

    private function createClassDefinition(string $classId): ClassDefinition
    {
        $class = new ClassDefinition();
        $class->setId($classId);
        $class->setName($classId);

        return $class;
    }

    private function createMessageBus(): MessageBusInterface
    {
        return $this->makeEmpty(MessageBusInterface::class, [
            'dispatch' => function (object $message, array $stamps = []): Envelope {
                $envelope = new Envelope($message, $stamps);
                $this->dispatched[] = $envelope;

                return $envelope;
            },
        ]);
    }

    /**
     * The settings store the queue keeps its marker in, in memory: one entry per scope and id, and
     * the value is never read - what it answers is whether there is an entry at all.
     */
    private function createSettingsStoreResolver(): SettingsStoreResolverInterface
    {
        return $this->makeEmpty(SettingsStoreResolverInterface::class, [
            'get' => fn (string $id, ?string $scope = null): ?SettingsStore
                => isset($this->settings[(string) $scope][$id]) ? new SettingsStore() : null,
            'set' => function (
                string $id,
                float|bool|int|string $data,
                string $type = 'string',
                ?string $scope = null,
            ): bool {
                $this->settings[(string) $scope][$id] = true;

                return true;
            },
            'delete' => function (string $id, ?string $scope = null): int {
                unset($this->settings[(string) $scope][$id]);

                return 1;
            },
        ]);
    }

    /**
     * The class ids a remap was handed over for, in order.
     *
     * @return list<string>
     */
    private function getRemappedClassIds(): array
    {
        $classIds = [];

        foreach ($this->dispatched as $envelope) {
            $message = $envelope->getMessage();

            if ($message instanceof SearchIndexRemapMessage) {
                $classIds[] = $message->getClassDefinitionId();
            }
        }

        return $classIds;
    }

    /**
     * The class ids a mapping update was handed on for, which is the generic data index bundle's own
     * message and the one a class save dispatches.
     *
     * @return list<string>
     */
    private function getMappingUpdateClassIds(): array
    {
        $classIds = [];

        foreach ($this->dispatched as $envelope) {
            $message = $envelope->getMessage();

            if ($message instanceof UpdateClassMappingMessage) {
                $classIds[] = (string) $message->getClassDefinition()->getId();
            }
        }

        return $classIds;
    }

    /**
     * The class ids whose remap was handed over with a delay stamp, so that the worker picks it up
     * after the writing request has committed.
     *
     * @return list<string>
     */
    private function getDelayedClassIds(): array
    {
        $classIds = [];

        foreach ($this->dispatched as $envelope) {
            $message = $envelope->getMessage();

            if ($message instanceof SearchIndexRemapMessage && $envelope->last(DelayStamp::class) !== null) {
                $classIds[] = $message->getClassDefinitionId();
            }
        }

        return $classIds;
    }

    /**
     * @return list<string>
     */
    private function readConstructorTypes(string $service): array
    {
        $types = [];

        foreach ((new ReflectionClass($service))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $types[] = $this->readParameterType($parameter);
        }

        return $types;
    }

    private function readParameterType(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();

        return $type instanceof ReflectionNamedType ? $type->getName() : '';
    }
}
