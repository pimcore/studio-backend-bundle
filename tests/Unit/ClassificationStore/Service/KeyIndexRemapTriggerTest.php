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

use Closure;
use Codeception\Test\Unit;
use Error;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Message\UpdateClassMappingMessage;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\SettingsStoreResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\StoreRepositoryInterface;
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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactBuilderInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryWriter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactKeyEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactRelationWriter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactTargetMatcher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ReconcilerInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreTransferService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\Tool\SettingsStore;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
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
 * rewritten by whoever drains the queue. It is also only queued once the writes it answers for have
 * committed - an apply that was refused or that failed rolled its key writes back, and remapping
 * their classes would re-index everything for a change the store does not carry.
 *
 * The queue is the real one throughout, over an in-memory stand-in for the settings store it keeps
 * its "already queued" marker in, so that the coalescing is observed where it happens rather than
 * on a double that was told to report it.
 *
 * The rest is the ordering contract of {@see SearchIndexRemapQueue}, a test per rule: the markers
 * exist before the hand-over is dispatched, the handler clears them, a marker nobody cleared stops
 * answering, a synchronous transport that runs the handler inside `dispatch()` is left with nothing
 * pending, and a `dispatch()` that throws takes its markers back. Nothing on that path may fail the
 * save it was triggered by - the key row is committed by then - so a store that cannot be resolved
 * and a queue that cannot be reached are both a log line and nothing more.
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

    private const string STORE_IDENTIFIER = '01STORE';

    private const string GROUP_IDENTIFIER = '01GROUP';

    private const string CLASS_ID = 'CAR';

    private const string OTHER_CLASS_ID = 'BIKE';

    /**
     * A class id made of digits alone, which is both a legal class id and the spelling of a store
     * id - the two markers may not share an entry because of it.
     */
    private const string NUMERIC_CLASS_ID = '2';

    private const string INDEXED = '{"fieldtype":"input","indexed":true}';

    private const string NOT_INDEXED = '{"fieldtype":"input","indexed":false}';

    private const string NO_MEMBER = '{"fieldtype":"input"}';

    /**
     * Comfortably past the window a queued remap's marker answers for.
     */
    private const int PAST_THE_PENDING_WINDOW = 7200;

    /**
     * @var list<Envelope> the messages handed to the bus, in order, with their stamps
     */
    private array $dispatched = [];

    /**
     * @var array<string, array<string, int>> the settings store, keyed by scope and by id, holding
     *                                        the second each marker was written at
     */
    private array $settings = [];

    /**
     * How often a store was resolved to the indices it reaches, which loads every class definition
     * and is not something a save that changed no flag may pay for.
     */
    private int $targetLookups = 0;

    /**
     * How often the trigger logged a failure instead of letting it reach the save.
     */
    private int $loggedFailures = 0;

    /**
     * @var list<bool> whether every marker the message stands for was already written when the
     *                 transport was handed it, one entry per remap dispatched
     */
    private array $markedDuringDispatch = [];

    /**
     * How many of the remaps still to be dispatched the transport refuses to take.
     */
    private int $dispatchFailures = 0;

    /**
     * What the transport does with a remap inside `dispatch()`: nothing, which is an asynchronous
     * one, or handle it, which is what a synchronous transport does.
     *
     * @var Closure(SearchIndexRemapMessage): void|null
     */
    private ?Closure $duringDispatch = null;

    /**
     * The queue the markers are read back through, so that they are observed through the service
     * that writes them rather than in the stand-in it writes into.
     */
    private ?SearchIndexRemapQueue $queue = null;

    public function _before(): void
    {
        $this->dispatched = [];
        $this->settings = [];
        $this->targetLookups = 0;
        $this->loggedFailures = 0;
        $this->markedDuringDispatch = [];
        $this->dispatchFailures = 0;
        $this->duringDispatch = null;
        $this->queue = null;
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
     * The key update endpoint needs no decision of its own about a rolled back write: its note is
     * taken after the save that committed the row, so an update that fails leaves nothing for the
     * flush to hand over - which is why that one flushes in a finally and the apply does not.
     */
    public function testAKeyUpdateThatFailsHandsNoRemapOver(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);
        $failure = new ElementSavingFailedException(self::KEY_ID, 'the key row is gone');

        try {
            $this->createKeyService($trigger, self::INDEXED, $failure)->updateKey(
                self::KEY_ID,
                new KeyUpdate(name: self::KEY_NAME, definition: ['fieldtype' => 'input', 'indexed' => false])
            );
            $this->fail('A failing key update cannot answer with a key detail.');
        } catch (ElementSavingFailedException) {
            // the failure is the caller's to handle; what it must not leave behind is the remap
        }

        $this->assertSame([], $this->getRemappedClassIds());
    }

    /**
     * A key the file creates is in no mapping yet and holds no value, so an import that creates a
     * whole vocabulary is not a re-index of every class its store reaches - however many of those
     * created keys carry the member.
     */
    public function testAKeyTheApplyCreatesEnqueuesNothing(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);
        $writer = $this->createEntryWriter($trigger);

        $writer->writeKey($this->createApplyContext(), null, $this->createKeyEntry(['indexed' => false]));
        $trigger->flush();

        $this->assertSame([], $this->getRemappedClassIds());
        $this->assertSame(0, $this->targetLookups);
    }

    /**
     * An apply runs inside one transaction ({@see ReconcilerInterface::apply()}), so the remap it
     * earns is handed over once that transaction has committed and the keys it wrote are the
     * store's.
     */
    public function testAnAppliedConfigurationHandsItsRemapOverOnceItHasCommitted(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $this
            ->createTransferService($trigger, new ApplyReport())
            ->importStore(self::STORE_ID, $this->createArtifactJson());

        $this->assertSame([self::CLASS_ID], $this->getRemappedClassIds());
    }

    /**
     * A refused apply is rolled back whole, so its key writes are not the store's: a remap of their
     * classes would re-index everything for a change nobody can read.
     */
    public function testARefusedApplyIsRolledBackAndHandsNoRemapOver(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        try {
            $this
                ->createTransferService($trigger, $this->createRefusedReport())
                ->importStore(self::STORE_ID, $this->createArtifactJson());
            $this->fail('A refused configuration cannot be applied.');
        } catch (ConflictException) {
            // the 409 is the refusal's own answer; what it must not leave behind is the remap
        }

        $this->assertSame([], $this->getRemappedClassIds());
    }

    /**
     * The console apply decides the same thing differently: it reports its conflicts rather than
     * throwing, and hands nothing over just the same.
     */
    public function testARefusedConsoleApplyHandsNoRemapOver(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $report = $this
            ->createTransferService($trigger, $this->createRefusedReport())
            ->applyArtifact($this->createArtifactJson(), false);

        $this->assertTrue($report->hasConflicts());
        $this->assertSame([], $this->getRemappedClassIds());
    }

    /**
     * And an apply that fails part way through is rolled back the same way - and leaves no note
     * behind either: the apply that follows it hands over the remap it earned itself, not the one
     * the failed apply never did.
     */
    public function testAnApplyThatFailsHandsNoRemapOverAndLeavesNoNoteBehind(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);
        $failure = new ElementSavingFailedException(self::KEY_ID, 'the key row is gone');

        try {
            $this
                ->createTransferService($trigger, new ApplyReport(), $failure)
                ->importStore(self::STORE_ID, $this->createArtifactJson());
            $this->fail('A failing apply cannot answer with a report.');
        } catch (ElementSavingFailedException) {
            // the failure is the caller's to handle; what it must not leave behind is the remap
        }

        $this->assertSame([], $this->getRemappedClassIds());

        $this
            ->createTransferService($trigger, new ApplyReport())
            ->importStore(self::STORE_ID, $this->createArtifactJson());

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
     * The first rule of the ordering contract: the marker is the claim that a message is on its way,
     * so it exists for the whole time that is true - which starts before the hand-over, not at its
     * end. Asked from inside `dispatch()`, the queue already answers that this remap is pending.
     */
    public function testEveryMarkerIsWrittenBeforeTheHandOverIsDispatched(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $trigger->noteFlagChange(self::STORE_ID, self::INDEXED, self::NOT_INDEXED);
        $trigger->flush();

        $this->assertSame([true], $this->markedDuringDispatch);
    }

    /**
     * On a synchronous transport the handler runs inside `dispatch()`, so it clears the markers
     * during the very call that wrote them - and finds them there to clear. Written the other way
     * round, the release would land on markers that do not exist yet and the write would land behind
     * it, leaving markers nothing ever clears and every later remap of that class skipped in silence.
     */
    public function testASynchronousTransportIsLeftWithNothingPending(): void
    {
        $handler = $this->createHandler();
        $this->duringDispatch = static function (SearchIndexRemapMessage $message) use ($handler): void {
            $handler($message);
        };
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $trigger->noteFlagChange(self::STORE_ID, self::INDEXED, self::NOT_INDEXED);
        $trigger->flush();
        $trigger->noteFlagChange(self::STORE_ID, self::NOT_INDEXED, self::INDEXED);
        $trigger->flush();

        $this->assertSame([self::CLASS_ID, self::CLASS_ID], $this->getRemappedClassIds());
        $this->assertSame([self::CLASS_ID, self::CLASS_ID], $this->getMappingUpdateClassIds());
    }

    /**
     * A hand-over that throws after its markers exist takes them back again, so a remap that did not
     * happen leaves nothing claiming that it did - and the save it was triggered by is answered all
     * the same, because the key row is committed by then.
     */
    public function testAHandOverThatFailsTakesItsMarkersBackAndAnswersTheSave(): void
    {
        $this->dispatchFailures = 1;
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $this->createKeyService($trigger, self::INDEXED)->updateKey(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, definition: ['fieldtype' => 'input', 'indexed' => false])
        );

        $this->assertSame([], $this->getRemappedClassIds());
        $this->assertSame(1, $this->loggedFailures);

        $trigger->noteFlagChange(self::STORE_ID, self::NOT_INDEXED, self::INDEXED);
        $trigger->flush();

        $this->assertSame([self::CLASS_ID], $this->getRemappedClassIds());
    }

    /**
     * Resolving a store to its indices happens under the same guard as the hand-over and against
     * Throwable, because it runs after the key row has been committed: a store that cannot be
     * resolved costs a log line, not the operator's edit turned into a 500.
     */
    public function testAStoreThatCannotBeResolvedIsLoggedAndDoesNotFailTheSave(): void
    {
        $trigger = $this->createTriggerWithUnresolvableStore();

        $detail = $this->createKeyService($trigger, self::INDEXED)->updateKey(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, definition: ['fieldtype' => 'input', 'indexed' => false])
        );

        $this->assertSame(self::KEY_ID, $detail->getId());
        $this->assertSame([], $this->getRemappedClassIds());
        $this->assertSame(1, $this->loggedFailures);
    }

    /**
     * The store's own marker is asked before the store is resolved, which is a read of every class
     * definition there is: the nineteen coalesced saves pay one settings store read each instead.
     */
    public function testACoalescedSaveDoesNotResolveTheStoreToItsIndices(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        for ($save = 0; $save < 20; $save++) {
            $trigger->noteFlagChange(self::STORE_ID, self::INDEXED, self::NOT_INDEXED);
            $trigger->flush();
        }

        $this->assertSame([self::CLASS_ID], $this->getRemappedClassIds());
        $this->assertSame(1, $this->targetLookups);
    }

    /**
     * And it decides nothing the class marker does not, because it is released with the very message
     * it was written with: once that is handled, the next flag change resolves the store again and
     * hands its own remap over.
     */
    public function testTheStoreMarkerIsReleasedWithTheRemapItWasWrittenWith(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $trigger->noteFlagChange(self::STORE_ID, self::INDEXED, self::NOT_INDEXED);
        $trigger->flush();
        $this->handleQueuedRemaps();

        $trigger->noteFlagChange(self::STORE_ID, self::NOT_INDEXED, self::INDEXED);
        $trigger->flush();

        $this->assertSame(2, $this->targetLookups);
        $this->assertSame([self::CLASS_ID, self::CLASS_ID], $this->getRemappedClassIds());
    }

    /**
     * A class id may be made of digits alone and a store id always is, so the two markers are kept
     * in scopes of their own: sharing one, the marker of store 2 would answer for the class whose id
     * is the string "2" and skip its remap with nothing queued for it anywhere.
     */
    public function testAClassIdAndAStoreIdThatLookAlikeAreNotOneMarker(): void
    {
        $trigger = $this->createTrigger([
            self::OTHER_STORE_ID => [self::CLASS_ID],
            self::STORE_ID => [self::NUMERIC_CLASS_ID],
        ]);

        $trigger->noteFlagChange(self::OTHER_STORE_ID, self::INDEXED, self::NOT_INDEXED);
        $trigger->flush();

        $trigger->noteFlagChange(self::STORE_ID, self::INDEXED, self::NOT_INDEXED);
        $trigger->flush();

        $this->assertSame([self::CLASS_ID, self::NUMERIC_CLASS_ID], $this->getRemappedClassIds());
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
     * A marker the handler never got to - a transport that dropped the message, retries that ran
     * out, a worker nobody started - stops answering once it is old enough. Without that, one lost
     * message would suppress every later remap of its class for good and say nothing about it.
     */
    public function testAMarkerOlderThanItsWindowNoLongerSuppressesTheNextRemap(): void
    {
        $trigger = $this->createTrigger([self::STORE_ID => [self::CLASS_ID]]);

        $trigger->noteFlagChange(self::STORE_ID, self::INDEXED, self::NOT_INDEXED);
        $trigger->flush();
        $this->ageTheQueuedMarkers();

        $trigger->noteFlagChange(self::STORE_ID, self::NOT_INDEXED, self::INDEXED);
        $trigger->flush();

        $this->assertSame([self::CLASS_ID, self::CLASS_ID], $this->getRemappedClassIds());
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
        return $this->createTriggerWithResolver(
            $this->makeEmpty(SearchIndexRemapTargetResolverInterface::class, [
                'resolveForStore' => function (int $storeId) use ($targetsByStore): array {
                    $this->targetLookups++;

                    return $targetsByStore[$storeId] ?? [];
                },
            ])
        );
    }

    /**
     * A store whose indices cannot be resolved at all. The failure is an Error rather than an
     * Exception, because that is the shape a class definition that no longer loads arrives in and
     * the guard has to hold for it too.
     */
    private function createTriggerWithUnresolvableStore(): SearchIndexRemapTrigger
    {
        return $this->createTriggerWithResolver(
            $this->makeEmpty(SearchIndexRemapTargetResolverInterface::class, [
                'resolveForStore' => function (): array {
                    $this->targetLookups++;

                    throw new Error('the class definition no longer loads');
                },
            ])
        );
    }

    private function createTriggerWithResolver(
        SearchIndexRemapTargetResolverInterface $targetResolver,
    ): SearchIndexRemapTrigger {
        return new SearchIndexRemapTrigger(
            new SearchIndexFlagResolver(),
            $targetResolver,
            $this->createQueue(),
            $this->makeEmpty(LoggerInterface::class, [
                'error' => function (): void {
                    $this->loggedFailures++;
                },
            ])
        );
    }

    /**
     * Drains the queued remaps the way the worker does, which is what releases their markers.
     */
    private function handleQueuedRemaps(): void
    {
        $handler = $this->createHandler();
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

    private function createKeyService(
        SearchIndexRemapTrigger $trigger,
        string $storedDefinition,
        ?Exception $failure = null,
    ): KeyService {
        $stored = $this->createStoredKey($storedDefinition);

        return new KeyService(
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(FilterMapperServiceInterface::class),
            $this->makeEmpty(KeyRelationResolverInterface::class),
            $this->makeEmpty(KeyRepositoryInterface::class, [
                'getById' => $stored,
                'create' => $stored,
                'update' => static function (int $id, KeyUpdate $parameters) use ($stored, $failure): KeyConfig {
                    if ($failure !== null) {
                        throw $failure;
                    }

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

    /**
     * The apply as its two halves actually run: the entry writer notes what its key writes changed,
     * and the transfer service is the one that knows whether the transaction they lived in
     * committed. The reconciler stands in for the sections in between, writing the one key the note
     * comes from.
     */
    private function createTransferService(
        SearchIndexRemapTrigger $trigger,
        ApplyReport $report,
        ?Exception $failure = null,
    ): StoreTransferService {
        $writer = $this->createEntryWriter($trigger);

        return new StoreTransferService(
            $this->makeEmpty(ArtifactBuilderInterface::class, ['stream' => []]),
            new ArtifactParser(),
            $this->makeEmpty(KeyRelationResolverInterface::class),
            $this->makeEmpty(ReconcilerInterface::class, [
                'apply' => function () use ($writer, $report, $failure): ApplyReport {
                    $writer->writeKey(
                        $this->createApplyContext(),
                        $this->createStoredKey(self::INDEXED),
                        $this->createKeyEntry(['indexed' => false])
                    );

                    if ($failure !== null) {
                        throw $failure;
                    }

                    return $report;
                },
            ]),
            $trigger,
            $this->makeEmpty(StoreRepositoryInterface::class, [
                'getById' => $this->createStore(),
                'getByIdentifier' => $this->createStore(),
            ])
        );
    }

    private function createRefusedReport(): ApplyReport
    {
        $report = new ApplyReport();
        $report->record(ApplyOutcome::CONFLICT, 'group "Measurements": the name is already another store\'s');

        return $report;
    }

    private function createStore(): StoreConfig
    {
        $store = new StoreConfig();
        $store->setId(self::STORE_ID);
        $store->setName('ETIM');
        $store->setIdentifier(self::STORE_IDENTIFIER);

        return $store;
    }

    private function createArtifactJson(): string
    {
        return (string) json_encode([
            'store' => ['identifier' => self::STORE_IDENTIFIER, 'name' => 'ETIM', 'description' => null],
            'subGroups' => [],
            'collections' => [],
            'groups' => [],
            'keys' => [],
            'collectionGroupRelations' => [],
            'keyGroupRelations' => [],
        ]);
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

    /**
     * The queue under test, over the in-memory settings store, and remembered so that the markers
     * can be read back through it from inside a dispatch.
     */
    private function createQueue(): SearchIndexRemapQueue
    {
        $this->queue = new SearchIndexRemapQueue($this->createMessageBus(), $this->createSettingsStoreResolver());

        return $this->queue;
    }

    private function createHandler(): SearchIndexRemapHandler
    {
        return new SearchIndexRemapHandler(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => fn (string $id): ClassDefinition => $this->createClassDefinition($id),
            ]),
            $this->createMessageBus(),
            $this->createQueue()
        );
    }

    private function createMessageBus(): MessageBusInterface
    {
        return $this->makeEmpty(MessageBusInterface::class, [
            'dispatch' => function (object $message, array $stamps = []): Envelope {
                if ($message instanceof SearchIndexRemapMessage) {
                    $this->markedDuringDispatch[] = $this->isEveryMarkerWritten($message);

                    if ($this->dispatchFailures > 0) {
                        $this->dispatchFailures--;

                        throw new TransportException('the transport cannot be reached');
                    }
                }

                $envelope = new Envelope($message, $stamps);
                $this->dispatched[] = $envelope;

                if ($message instanceof SearchIndexRemapMessage && $this->duringDispatch !== null) {
                    ($this->duringDispatch)($message);
                }

                return $envelope;
            },
        ]);
    }

    /**
     * Whether the queue already answers that everything this message stands for is pending, asked
     * through the queue itself rather than of the stand-in it writes into.
     */
    private function isEveryMarkerWritten(SearchIndexRemapMessage $message): bool
    {
        if ($this->queue === null || !$this->queue->hasPendingRemap($message->getClassDefinitionId())) {
            return false;
        }

        foreach ($message->getStoreIds() as $storeId) {
            if (!$this->queue->hasPendingStoreRemap($storeId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The settings store the queue keeps its marker in, in memory: one entry per scope and id,
     * holding the second the hand-over was written at.
     */
    private function createSettingsStoreResolver(): SettingsStoreResolverInterface
    {
        return $this->makeEmpty(SettingsStoreResolverInterface::class, [
            'get' => function (string $id, ?string $scope = null): ?SettingsStore {
                $writtenAt = $this->settings[(string) $scope][$id] ?? null;

                if ($writtenAt === null) {
                    return null;
                }

                $marker = new SettingsStore();
                $marker->setType(SettingsStore::TYPE_INTEGER);
                $marker->setData($writtenAt);

                return $marker;
            },
            'set' => function (
                string $id,
                float|bool|int|string $data,
                string $type = 'string',
                ?string $scope = null,
            ): bool {
                $this->settings[(string) $scope][$id] = (int) $data;

                return true;
            },
            'delete' => function (string $id, ?string $scope = null): int {
                unset($this->settings[(string) $scope][$id]);

                return 1;
            },
        ]);
    }

    /**
     * Moves every marker back past the window a queued remap answers for, which is what a message
     * the handler never saw leaves behind.
     */
    private function ageTheQueuedMarkers(): void
    {
        foreach ($this->settings as $scope => $markers) {
            foreach ($markers as $id => $writtenAt) {
                $this->settings[$scope][$id] = $writtenAt - self::PAST_THE_PENDING_WINDOW;
            }
        }
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
