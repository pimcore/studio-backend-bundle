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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository\Configuration;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Exception;
use Pimcore;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyWriteResult;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactKeyEntry;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Cache\Core\CoreCacheHandler;
use Pimcore\Element\MarshallerService;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Loader\DataLoaderInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao as KeyConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyTypeSupport;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
final class KeyRepositoryTest extends Unit
{
    private const int KEY_ID = 7;

    private const string KEY_NAME = 'weight';

    private const string STORED_TITLE = 'Weight';

    private const string STORED_DESCRIPTION = 'The weight of the product';

    private const string IDENTIFIER = '01K9T0ZQ0VX8W4S1R7J3A2B5C6';

    /**
     * The definition the locking read answers with, which is the row as of the moment the write
     * took it rather than whatever the model was loaded with.
     */
    private const string HELD_DEFINITION = '{"fieldtype":"input","indexed":true}';

    /**
     * What the model was loaded with, which a competing writer has since replaced: it differs from
     * the locking read's answer so that reporting the wrong one of the two cannot pass.
     */
    private const string LOADED_DEFINITION = '{"fieldtype":"input","indexed":false}';

    private const string BEGIN = 'beginTransaction';

    private const string COMMIT = 'commit';

    private const string HOLD_KEY_QUERY =
        'SELECT `definition` FROM `classificationstore_keys` WHERE `id` = ? FOR UPDATE';

    private const string HOLD_ENABLED_QUERY =
        'SELECT * FROM `classificationstore_keys` WHERE `id` = ? FOR UPDATE';

    private ?KernelInterface $kernelBeforeStub = null;

    private InMemoryKeyLocalizationResolver $keyLocalizationResolver;

    /**
     * An empty container makes `save()` give up at `Pimcore\Db::get()`, with an exception the
     * repository converts, once it has written the model.
     *
     * @throws Exception
     */
    public function _before(): void
    {
        $this->keyLocalizationResolver = new InMemoryKeyLocalizationResolver();
        $this->kernelBeforeStub = Pimcore::getKernel();
        Pimcore::setKernel($this->makeEmpty(KernelInterface::class, ['getContainer' => new Container()]));
    }

    protected function _after(): void
    {
        // The suite is shuffled and Pimcore::setKernel() does not take null, so put the previous
        // kernel back the only way there is.
        (new ReflectionProperty(Pimcore::class, 'kernel'))->setValue(null, $this->kernelBeforeStub);
    }

    /**
     * @dataProvider titleProvider
     *
     * @throws Exception
     */
    public function testTheStoredTitleFollowsWhetherTheFieldWasSupplied(
        ?string $title,
        bool $supplied,
        string $expected
    ): void {
        $config = $this->createStoredKeyConfig();
        $repository = $this->createRepository($config);

        $this->write(static fn (): KeyWriteResult => $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, title: $title, titleSupplied: $supplied)
        ));

        $this->assertSame($expected, $config->getTitle());
    }

    /**
     * @return array<string, array{0: ?string, 1: bool, 2: string}>
     */
    public static function titleProvider(): array
    {
        return [
            'absent leaves the stored title alone' => [null, false, self::STORED_TITLE],
            'present and null clears the title' => [null, true, ''],
            'present and empty clears the title' => ['', true, ''],
            'present and filled replaces the title' => ['Mass', true, 'Mass'],
        ];
    }

    /**
     * @dataProvider descriptionProvider
     *
     * @throws Exception
     */
    public function testTheStoredDescriptionFollowsWhetherTheFieldWasSupplied(
        ?string $description,
        bool $supplied,
        string $expected
    ): void {
        $config = $this->createStoredKeyConfig();
        $repository = $this->createRepository($config);

        $this->write(static fn (): KeyWriteResult => $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, description: $description, descriptionSupplied: $supplied)
        ));

        $this->assertSame($expected, $config->getDescription());
    }

    /**
     * @return array<string, array{0: ?string, 1: bool, 2: string}>
     */
    public static function descriptionProvider(): array
    {
        return [
            'absent leaves the stored description alone' => [null, false, self::STORED_DESCRIPTION],
            'present and null clears the description' => [null, true, ''],
            'present and empty clears the description' => ['', true, ''],
            'present and filled replaces the description' => ['The mass', true, 'The mass'],
        ];
    }

    /**
     * A definition carrying a title of its own does not refill the cleared title, and is itself
     * left untouched. Only the model is asserted here, because this suite has no database: that
     * the empty title survives `KeyConfig::save()` and a reload is covered in core, by
     * `tests/Model/DataType/ClassificationStore/KeyTitleTest.php`, where the title column is
     * seeded from the definition only for a key whose title was neither loaded nor set.
     *
     * @throws Exception
     */
    public function testClearingATitleEmptiesItAndLeavesTheDefinitionAlone(): void
    {
        $config = $this->createStoredKeyConfig();
        $config->setDefinition('{"title":"Weight"}');
        $repository = $this->createRepository($config);

        $this->write(static fn (): KeyWriteResult => $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, titleSupplied: true)
        ));

        $this->assertSame('', $config->getTitle());
        $this->assertSame('{"title":"Weight"}', $config->getDefinition());
    }

    /**
     * The definition blob is stored as it arrived, which is what carries the search index flag a
     * created assignment of this key defaults from: there is no property of its own for it on the
     * update schema, and nothing in the write path reads the blob apart.
     *
     * @throws Exception
     */
    public function testADefinitionCarryingTheSearchIndexFlagIsStoredVerbatim(): void
    {
        $config = $this->createStoredKeyConfig();
        $repository = $this->createRepository($config);

        $this->write(static fn (): KeyWriteResult => $repository->update(
            self::KEY_ID,
            new KeyUpdate(
                name: self::KEY_NAME,
                definition: ['fieldtype' => 'input', 'indexed' => false]
            )
        ));

        $this->assertSame('{"fieldtype":"input","indexed":false}', $config->getDefinition());
    }

    /**
     * A directly constructed DTO carries no presence flags, so without the inference in
     * KeyUpdate both writes below are skipped and the stored values survive unchanged.
     *
     * @throws Exception
     */
    public function testDirectConstructionStillWritesTheTitleAndTheDescription(): void
    {
        $config = $this->createStoredKeyConfig();
        $repository = $this->createRepository($config);

        $this->write(static fn (): KeyWriteResult => $repository->update(
            self::KEY_ID,
            new KeyUpdate('weight', 'Mass', 'The mass of the product')
        ));

        $this->assertSame('Mass', $config->getTitle());
        $this->assertSame('The mass of the product', $config->getDescription());
    }

    /**
     * `localized` follows the same absent-versus-supplied rule as the title, with one difference:
     * null is a value of its own here, so a supplied null writes NULL rather than clearing to a
     * neutral string. Only an omitted member leaves the stored flag alone.
     *
     * @dataProvider localizedProvider
     *
     * @throws Exception
     */
    public function testTheStoredLocalizationFollowsWhetherTheFieldWasSupplied(
        ?bool $stored,
        ?bool $localized,
        bool $supplied,
        ?bool $expected
    ): void {
        $config = $this->createStoredKeyConfig();
        $this->keyLocalizationResolver->setLocalized($config, $stored);
        $repository = $this->createRepository($config);

        $this->write(static fn (): KeyWriteResult => $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, localized: $localized, localizedSupplied: $supplied)
        ));

        $this->assertSame($expected, $this->keyLocalizationResolver->getLocalized($config));
    }

    /**
     * Every row's stored value differs from the value the request carries, so each one goes red if
     * the write it describes stops happening - a row whose two values agree would pass either way.
     *
     * @return array<string, array{0: ?bool, 1: ?bool, 2: bool, 3: ?bool}>
     */
    public static function localizedProvider(): array
    {
        return [
            'absent leaves the stored flag alone' => [true, null, false, true],
            'present and null stores no flag' => [true, null, true, null],
            'present and true stores true' => [false, true, true, true],
            'present and false stores false' => [true, false, true, false],
            'true without a flag still counts as supplied' => [false, true, false, true],
            'false without a flag still counts as supplied' => [true, false, false, false],
        ];
    }

    /**
     * Apply restores what the entry carries without asking whether it carries anything: an entry
     * with no `localized` member writes NULL, so a file exported before the flag existed leaves
     * every key stating nothing rather than keeping whatever the target happened to hold.
     *
     * @dataProvider artifactLocalizedProvider
     *
     * @throws Exception
     */
    public function testApplyWritesTheLocalizationOfTheEntryUnconditionally(?bool $stored, ?bool $localized): void
    {
        $config = $this->createStoredKeyConfig();
        $this->keyLocalizationResolver->setLocalized($config, $stored);
        $repository = $this->createRepository($config);

        $this->write(static fn (): KeyWriteResult => $repository->saveFromArtifact(
            $config,
            1,
            new ArtifactKeyEntry(
                self::IDENTIFIER,
                self::KEY_NAME,
                self::STORED_TITLE,
                self::STORED_DESCRIPTION,
                'input',
                ['fieldtype' => 'input'],
                true,
                $localized
            )
        ));

        $this->assertSame($localized, $this->keyLocalizationResolver->getLocalized($config));
    }

    /**
     * The stored value is the opposite of the entry's on every row, so none of them can pass with
     * the write removed.
     *
     * @return array<string, array{0: ?bool, 1: ?bool}>
     */
    public static function artifactLocalizedProvider(): array
    {
        return [
            'an entry stating nothing writes no flag' => [true, null],
            'an entry flagged true writes true' => [false, true],
            'an entry flagged false writes false' => [true, false],
        ];
    }

    /**
     * The update holds the row it writes, and holds it before it reads the definition it reports
     * back. That lock is what makes the two definitions a search index flag comparison is made of
     * - the one the row carried and the one the write left behind - belong to the same row state:
     * read outside it, two requests changing the same key each compare against a value the other
     * has already replaced, and the flag change only one of them witnessed is queued by neither.
     *
     * @throws Exception
     */
    public function testTheUpdateHoldsTheRowItWritesBeforeReadingIt(): void
    {
        $calls = [];
        $repository = $this->createRepository(
            $this->createStoredKeyConfig(),
            $this->createHoldingConnection($calls)
        );

        $this->write(static fn (): KeyWriteResult => $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, definition: ['fieldtype' => 'input', 'indexed' => false])
        ));

        $this->assertSame([self::BEGIN, self::HOLD_KEY_QUERY], $calls);
    }

    /**
     * The pairs follow the rule of the title: a payload that omits them leaves the stored ones alone,
     * a supplied object replaces them whole, and a supplied null or empty map clears them.
     *
     * @throws Exception
     */
    public function testTheUpdateWritesThePairsOnlyWhenThePayloadSuppliedThem(): void
    {
        $calls = [];
        $config = $this->createSavableKeyConfig();
        $config->setDefinition(self::LOADED_DEFINITION);
        $repository = $this->createRepository($config, $this->createCommittingConnection($calls));

        $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME)
        );
        $this->assertFalse($config->isCustomMetadataInitialized());

        $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, customMetadata: ['indexed' => 'yes'])
        );
        $this->assertSame(['indexed' => 'yes'], $config->getCustomMetadata());

        $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME)
        );
        $this->assertSame(['indexed' => 'yes'], $config->getCustomMetadata());

        $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, customMetadata: ['source' => 'erp'])
        );
        $this->assertSame(['source' => 'erp'], $config->getCustomMetadata());

        $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, customMetadataSupplied: true)
        );
        $this->assertNull($config->getCustomMetadata());

        $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, customMetadata: [])
        );
        $this->assertNull($config->getCustomMetadata());
    }

    /**
     * The apply's key write takes the pairs only from an entry that supplies them.
     *
     * @throws Exception
     */
    public function testTheApplyWritesThePairsOnlyWhenTheEntrySuppliesThem(): void
    {
        $calls = [];
        $config = $this->createSavableKeyConfig();
        $config->setDefinition(self::LOADED_DEFINITION);
        $config->setCustomMetadata(['indexed' => 'yes']);
        $repository = $this->createRepository($config, $this->createCommittingConnection($calls));

        $repository->saveFromArtifact($config, 1, $this->createArtifactEntry());
        $this->assertSame(['indexed' => 'yes'], $config->getCustomMetadata());

        $entry = new ArtifactKeyEntry(
            self::IDENTIFIER,
            self::KEY_NAME,
            self::STORED_TITLE,
            self::STORED_DESCRIPTION,
            'input',
            ['fieldtype' => 'input'],
            true,
            null,
            null,
            true
        );
        $repository->saveFromArtifact($config, 1, $entry);
        $this->assertNull($config->getCustomMetadata());
    }

    /**
     * The apply's key write holds its row the same way - it runs long enough for the key endpoint to
     * have committed over the target it read - and holds nothing for a key it creates, which has no
     * row to hold and replaces no definition.
     *
     * @throws Exception
     */
    public function testTheApplyHoldsTheRowItOverwritesAndNoneForAKeyItCreates(): void
    {
        $entry = $this->createArtifactEntry();
        $calls = [];
        $config = $this->createStoredKeyConfig();
        $repository = $this->createRepository($config, $this->createHoldingConnection($calls));

        $this->write(static fn (): KeyWriteResult => $repository->saveFromArtifact($config, 1, $entry));

        $this->assertSame([self::BEGIN, self::HOLD_KEY_QUERY], $calls);

        $calls = [];
        $repository = $this->createRepository($config, $this->createHoldingConnection($calls));

        $this->write(static fn (): KeyWriteResult => $repository->saveFromArtifact(null, 1, $entry));

        $this->assertSame([self::BEGIN], $calls);
    }

    /**
     * The write as it goes when nothing fails, which is the only state in which the thing the key
     * endpoint actually consumes exists: the {@see KeyWriteResult}.
     *
     * Two rules are asserted on it, and neither is observable from a write that threw. The commit
     * happens inside the call rather than after it, because a caller handed the result is told the
     * row is the store's and notes a flag change on the strength of it
     * ({@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\KeyService::updateKey()}).
     * And `definitionBefore` is the definition the locking read answered with and not the one the
     * model was loaded with: the two differ here, exactly as they do for a row a competing writer
     * changed between the load and the lock, and it is the locked value the flag comparison has to
     * be made against.
     *
     * The save succeeds because the model is given a dao of its own rather than the one it would
     * detect, which is what needs a database. Everything `KeyConfig::save()` reaches past that - the
     * cache handler and the event dispatcher - is served from the container for the length of this
     * test.
     *
     * @throws Exception
     */
    public function testASuccessfulWriteCommitsBeforeItAnswersWithTheDefinitionItLocked(): void
    {
        $calls = [];
        $config = $this->createSavableKeyConfig();
        // the value the model was loaded with, which is NOT the one the locking read answers with
        $config->setDefinition(self::LOADED_DEFINITION);
        $repository = $this->createRepository($config, $this->createCommittingConnection($calls));

        $result = $repository->update(
            self::KEY_ID,
            new KeyUpdate(name: self::KEY_NAME, definition: ['fieldtype' => 'input', 'indexed' => false])
        );

        $this->assertSame(
            [self::BEGIN, self::HOLD_KEY_QUERY, self::COMMIT],
            $calls,
            'The write commits inside the call that answers with the result.'
        );
        $this->assertSame(self::HELD_DEFINITION, $result->getDefinitionBefore());
        $this->assertSame($config, $result->getConfig());
        $this->assertSame('{"fieldtype":"input","indexed":false}', $result->getConfig()->getDefinition());
    }

    /**
     * The apply's key write answers the same way, over the row it overwrites: it is the one write
     * that runs long enough for the key endpoint to have committed over the target it read, so the
     * definition it reports has to be the locked one there too.
     *
     * @throws Exception
     */
    public function testASuccessfulApplyWriteCommitsBeforeItAnswersWithTheDefinitionItLocked(): void
    {
        $calls = [];
        $config = $this->createSavableKeyConfig();
        $config->setDefinition(self::LOADED_DEFINITION);
        $repository = $this->createRepository($config, $this->createCommittingConnection($calls));

        $result = $repository->saveFromArtifact($config, 1, $this->createArtifactEntry());

        $this->assertSame([self::BEGIN, self::HOLD_KEY_QUERY, self::COMMIT], $calls);
        $this->assertSame(self::HELD_DEFINITION, $result->getDefinitionBefore());
        $this->assertSame('{"fieldtype":"input"}', $result->getConfig()->getDefinition());
    }

    /**
     * The relation claim of the restore (no relation row, no DELETE) is core's `KeyConfig\Dao::save()`
     * to keep, and that dao is replaced here, so it cannot be observed in this suite. What is asserted
     * is what the repository itself asks of the database: it holds the row, reads the stored flag
     * from it, and commits - and the model it saves carries nothing but the flag changed.
     *
     * @throws Exception
     */
    public function testRestoreWritesTheEnabledFlagAndNothingElseWithTheRowHeld(): void
    {
        $calls = [];
        $saves = 0;
        $config = $this->createSavableKeyConfig();
        $config->setEnabled(false);
        $config->setDao($this->makeEmpty(KeyConfigDao::class, [
            'save' => static function () use (&$saves): void {
                ++$saves;
            },
        ]));
        $repository = $this->createRepository($config, $this->createRestoreConnection($calls, 0));

        $restored = $repository->restore(self::KEY_ID);

        $this->assertSame($config, $restored);
        $this->assertTrue($restored->getEnabled());
        $this->assertSame(1, $saves);
        $this->assertSame(self::KEY_NAME, $restored->getName());
        $this->assertSame(self::STORED_TITLE, $restored->getTitle());
        $this->assertSame(self::STORED_DESCRIPTION, $restored->getDescription());
        $this->assertSame(
            [self::BEGIN, self::HOLD_KEY_QUERY, self::HOLD_ENABLED_QUERY, self::COMMIT],
            $calls,
            'The restore holds the row for the whole write and commits.'
        );
    }

    /**
     * @throws Exception
     */
    public function testRestoringAnEnabledKeyWritesNothing(): void
    {
        $calls = [];
        $saves = 0;
        $config = $this->createSavableKeyConfig();
        $config->setEnabled(true);
        $config->setDao($this->makeEmpty(KeyConfigDao::class, [
            'save' => static function () use (&$saves): void {
                ++$saves;
            },
        ]));

        $restored = $this->createRepository($config, $this->createRestoreConnection($calls, 1))
            ->restore(self::KEY_ID);

        $this->assertTrue($restored->getEnabled());
        $this->assertSame(0, $saves);
    }

    /**
     * The model may be a cached one: the flag that decides the write is the held row's.
     *
     * @throws Exception
     */
    public function testRestoreDecidesOnTheStoredFlagAndNotOnTheModel(): void
    {
        $saves = 0;
        $dao = $this->makeEmpty(KeyConfigDao::class, [
            'save' => static function () use (&$saves): void {
                ++$saves;
            },
        ]);
        $calls = [];

        $staleEnabled = $this->createSavableKeyConfig();
        $staleEnabled->setEnabled(true);
        $staleEnabled->setDao($dao);
        $this->createRepository($staleEnabled, $this->createRestoreConnection($calls, 0))->restore(self::KEY_ID);
        $this->assertSame(1, $saves, 'A row still disabled is written although the model says enabled.');

        $staleDisabled = $this->createSavableKeyConfig();
        $staleDisabled->setEnabled(false);
        $staleDisabled->setDao($dao);
        $restored = $this->createRepository($staleDisabled, $this->createRestoreConnection($calls, 1))
            ->restore(self::KEY_ID);
        $this->assertSame(1, $saves, 'A row already enabled is not written although the model says disabled.');
        $this->assertTrue($restored->getEnabled());
    }

    /**
     * `save()` writes every column, so a cached model is refreshed from the held row first.
     *
     * @throws Exception
     */
    public function testRestoreRefreshesAStaleModelFromTheHeldRowBeforeSaving(): void
    {
        $savedTitle = null;
        $config = $this->createSavableKeyConfig();
        $config->setEnabled(false);
        $config->setTitle('stale title');
        $dao = $this->makeEmpty(KeyConfigDao::class, [
            'save' => static function () use (&$savedTitle, $config): void {
                $savedTitle = $config->getTitle();
            },
        ]);
        $config->setDao($dao);
        $connection = $this->makeEmpty(Connection::class, [
            'isTransactionActive' => false,
            'fetchOne' => self::HELD_DEFINITION,
            'fetchAssociative' => ['id' => self::KEY_ID, 'enabled' => 0, 'title' => 'newer title'],
        ]);

        $this->createRepository($config, $connection)->restore(self::KEY_ID);

        $this->assertSame('newer title', $savedTitle);
        $this->assertTrue($config->getEnabled());
    }

    /**
     * @throws Exception
     */
    public function testThePageIsScopedToTheViewItIsAskedFor(): void
    {
        foreach ([false, true] as $disabledOnly) {
            $queries = [];
            $connection = $this->makeEmpty(Connection::class, [
                'fetchAssociative' => static function (string $query) use (&$queries): array {
                    $queries[] = $query;

                    return ['name' => 'weight', 'position' => 1];
                },
            ]);

            $this->createRepository($this->createStoredKeyConfig(), $connection)
                ->getPageForId('keys', self::KEY_ID, 1, 10, 'name', 'ASC', $disabledOnly);

            $this->assertCount(2, $queries);
            foreach ($queries as $query) {
                $this->assertSame(
                    $disabledOnly,
                    str_contains($query, '(enabled IS NULL OR enabled = 0)'),
                    $query
                );
                $this->assertSame(!$disabledOnly, str_contains($query, 'AND enabled = 1'), $query);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testRestoringAnUnknownKeyIsNotFound(): void
    {
        $calls = [];
        $repository = new KeyRepository(
            $this->makeEmpty(KeyConfigResolverInterface::class, ['getById' => null]),
            $this->keyLocalizationResolver,
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $this->createRestoreConnection($calls, 0),
        );

        $this->expectException(NotFoundException::class);

        $repository->restore(self::KEY_ID);
    }

    /**
     * @throws Exception
     */
    public function testAFailingSaveWhileRestoringIsAnElementSavingFailure(): void
    {
        $config = $this->createSavableKeyConfig();
        // a stale cached model: it says enabled while the held row (answered 0 below) is disabled
        $config->setEnabled(true);
        $config->setDao($this->makeEmpty(KeyConfigDao::class, [
            'save' => static function (): void {
                throw new Exception('database is gone');
            },
        ]));

        $this->expectException(ElementSavingFailedException::class);

        $calls = [];
        try {
            $this->createRepository($config, $this->createRestoreConnection($calls, 0))->restore(self::KEY_ID);
        } finally {
            $this->assertFalse($config->getEnabled(), 'A failed write left the cached model reporting enabled.');
        }
    }

    /**
     * A connection that records the transaction and the two locking reads of a restore, and answers
     * the stored `enabled` flag with the given value.
     *
     * @param list<string> $calls
     *
     * @throws Exception
     */
    private function createRestoreConnection(array &$calls, int $storedEnabled): Connection
    {
        return $this->makeEmpty(Connection::class, [
            'isTransactionActive' => false,
            'beginTransaction' => static function () use (&$calls): void {
                $calls[] = self::BEGIN;
            },
            'fetchOne' => static function (string $query) use (&$calls): string {
                $calls[] = $query;

                return self::HELD_DEFINITION;
            },
            'fetchAssociative' => static function (string $query) use (&$calls, $storedEnabled): array {
                $calls[] = $query;

                return ['id' => self::KEY_ID, 'enabled' => $storedEnabled];
            },
            'commit' => static function () use (&$calls): void {
                $calls[] = self::COMMIT;
            },
            'rollBack' => static function () use (&$calls): void {
                $calls[] = 'ROLLBACK';
            },
        ]);
    }

    /**
     * A key config whose save does not need a database: the dao it would otherwise detect is the
     * one part of `KeyConfig::save()` that cannot run without one.
     *
     * `KeyTypeSupport` is served too, because `KeyConfig::save()` resolves it from the container
     * before it writes anything: it asks whether the key's field type may be defined at all and
     * whether the engine of the store can hold it
     * ({@see \Pimcore\Model\DataObject\Classificationstore\KeyConfig::save()}).
     *
     * The real one, because the class is final and cannot be stubbed, over the two collaborators it
     * reads: a loader that resolves the type to an `input` definition, and a marshaller service
     * holding no marshaller for the classification store format. Those are the answers core's own
     * wiring gives for an `input` key, so the gate runs to the verdict it reaches in production -
     * `input` normalizes to `mixed`, so the two value columns hold it and the engine is never asked
     * - rather than being skipped.
     *
     * @throws Exception
     */
    private function createSavableKeyConfig(): KeyConfig
    {
        $container = new Container();
        $container->set(CoreCacheHandler::class, $this->makeEmpty(CoreCacheHandler::class));
        $container->set('event_dispatcher', new EventDispatcher());
        $container->set(KeyTypeSupport::class, new KeyTypeSupport(
            $this->makeEmpty(DataLoaderInterface::class, [
                'supports' => true,
                'build' => new Input(),
            ]),
            new MarshallerService(new ServiceLocator([]))
        ));
        Pimcore::setKernel($this->makeEmpty(KernelInterface::class, ['getContainer' => $container]));

        $config = $this->createStoredKeyConfig();
        $config->setDao($this->makeEmpty(KeyConfigDao::class));

        return $config;
    }

    /**
     * A connection that records what the write asked of it and commits, so that the order the
     * commit sits in relative to the answer is asserted rather than assumed.
     *
     * @param list<string> $calls
     *
     * @throws Exception
     */
    private function createCommittingConnection(array &$calls): Connection
    {
        return $this->makeEmpty(Connection::class, [
            'isTransactionActive' => false,
            'beginTransaction' => static function () use (&$calls): void {
                $calls[] = self::BEGIN;
            },
            'fetchOne' => static function (string $query) use (&$calls): string {
                $calls[] = $query;

                return self::HELD_DEFINITION;
            },
            'commit' => static function () use (&$calls): void {
                $calls[] = self::COMMIT;
            },
        ]);
    }

    private function createArtifactEntry(): ArtifactKeyEntry
    {
        return new ArtifactKeyEntry(
            self::IDENTIFIER,
            self::KEY_NAME,
            self::STORED_TITLE,
            self::STORED_DESCRIPTION,
            'input',
            ['fieldtype' => 'input'],
            true,
            null
        );
    }

    private function createStoredKeyConfig(): KeyConfig
    {
        $config = new KeyConfig();
        $config->setId(self::KEY_ID);
        $config->setName(self::KEY_NAME);
        $config->setTitle(self::STORED_TITLE);
        $config->setDescription(self::STORED_DESCRIPTION);

        return $config;
    }

    /**
     * Runs the write and swallows the failure the missing database causes: `save()` gives up after
     * the repository has written the model, so the model is the stored value this suite can assert.
     */
    private function write(callable $write): void
    {
        try {
            $write();

            $this->fail('Expected the save to fail without a database.');
        } catch (ElementSavingFailedException) {
            // the write the assertions are about has happened by then
        }
    }

    /**
     * @throws Exception
     */
    private function createRepository(KeyConfig $config, ?Connection $connection = null): KeyRepository
    {
        return new KeyRepository(
            $this->makeEmpty(KeyConfigResolverInterface::class, [
                'getById' => $config,
            ]),
            $this->keyLocalizationResolver,
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $connection ?? $this->makeEmpty(Connection::class),
        );
    }

    /**
     * A connection that records what the write asked of it, in order, so that the lock the write
     * takes is asserted rather than assumed.
     *
     * @param list<string> $calls
     *
     * @throws Exception
     */
    private function createHoldingConnection(array &$calls): Connection
    {
        return $this->makeEmpty(Connection::class, [
            'isTransactionActive' => false,
            'beginTransaction' => static function () use (&$calls): void {
                $calls[] = self::BEGIN;
            },
            'fetchOne' => static function (string $query) use (&$calls): string {
                $calls[] = $query;

                return self::HELD_DEFINITION;
            },
        ]);
    }
}
