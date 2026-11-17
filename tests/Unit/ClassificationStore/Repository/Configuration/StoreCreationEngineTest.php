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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\StoreConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\StoreRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreEngineResolverInterface;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Throwable;

/**
 * Which store configuration is given an engine, and which is not.
 *
 * A store created through Studio used to be written with an empty engine column, and an empty
 * column means the default - the EAV engine - so every store a user made landed on the engine that
 * can hold the least. The two creation paths now ask what a new store is put on; the update path
 * and an apply writing into a store that already exists do not ask at all, because an existing
 * row's engine is a data migration rather than a setting.
 *
 * There is no database in this suite, so what is asserted here is who is asked and whose engine is
 * written - the NAME that answer carries is asserted against a real core engine resolver in
 * {@see \Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\StoreEngineResolverTest}.
 *
 * @internal
 */
final class StoreCreationEngineTest extends Unit
{
    private const string DOCUMENT = 'document';

    private const string STORED_ENGINE = 'eav';

    private const string IDENTIFIER = '01K9T0ZQ0VX8W4S1R7J3A2B5C6';

    private const string NAME = 'Technical attributes';

    private int $asked = 0;

    protected function _before(): void
    {
        $this->asked = 0;
    }

    public function testCreatingAStoreAsksWhichEngineANewStoreIsPutOn(): void
    {
        $this->swallowTheSave(fn (): mixed => $this->createRepository()->create(self::NAME));

        $this->assertSame(1, $this->asked, 'the creation path asks core what a new store is put on');
    }

    /**
     * The import path creates stores too, and demo-26 shows it is how the engineless ones got
     * there: two of its three stores were reproduced onto rows that were never given an engine.
     */
    public function testAnApplyCreatingTheStoreAsksTheSameQuestion(): void
    {
        $this->swallowTheSave(
            fn (): mixed => $this->createRepository()->saveFromArtifact(null, self::IDENTIFIER, self::NAME, null)
        );

        $this->assertSame(1, $this->asked, 'a row the apply creates is given the engine of a new store');
    }

    /**
     * The control, and the half that must not change: a store that already has a row keeps the
     * engine it has, whatever that is. Writing one onto an existing row would re-point a store at
     * storage holding none of its values, which is what the flip command exists to do properly.
     */
    public function testAnApplyWritingIntoAnExistingStoreLeavesItsEngineAlone(): void
    {
        $existing = new StoreConfig();
        $existing->setId(22);
        $existing->setEngine(self::STORED_ENGINE);

        $this->swallowTheSave(
            fn (): mixed => $this->createRepository()->saveFromArtifact($existing, self::IDENTIFIER, self::NAME, null)
        );

        $this->assertSame(0, $this->asked, 'an existing row is not asked about');
        $this->assertSame(self::STORED_ENGINE, $existing->getEngine(), 'and keeps the engine it stores');
    }

    /**
     * An installation whose core cannot be asked answers nothing, and then nothing is written: such
     * a store means the default engine the way every store did before the column existed, rather
     * than carrying a name no upgrade path writes.
     */
    public function testAnInstallationAnsweringNothingWritesNoEngine(): void
    {
        $repository = $this->createRepository(null);

        $this->swallowTheSave(fn (): mixed => $repository->create(self::NAME));

        $this->assertSame(1, $this->asked);
    }

    private function createRepository(?string $engineOfANewStore = self::DOCUMENT): StoreRepository
    {
        return new StoreRepository(
            $this->makeEmpty(StoreConfigResolverInterface::class, ['getByName' => null]),
            $this->makeEmpty(Connection::class),
            $this->makeEmpty(StoreEngineResolverInterface::class, [
                'getEngineForNewStore' => function () use ($engineOfANewStore): ?string {
                    ++$this->asked;

                    return $engineOfANewStore;
                },
            ])
        );
    }

    /**
     * Runs the call and swallows the save failing: there is no database in this suite, so every
     * one of these paths fails at `StoreConfig::save()` - after the engine decision, which is the
     * thing under test.
     *
     * Only what the save raises is swallowed. The absent connection surfaces as an `Error` rather
     * than an `Exception`, so catching by type would either let the save through or hide an
     * `Error` from the engine decision itself, which these tests would then pass without noticing.
     */
    private function swallowTheSave(callable $call): void
    {
        try {
            $call();
        } catch (Throwable $throwable) {
            if (!$this->raisedByTheSave($throwable)) {
                throw $throwable;
            }
        }
    }

    /**
     * Whether the save is on the way down to where this was raised, which is what makes it the
     * absent database rather than the code under test.
     */
    private function raisedByTheSave(Throwable $throwable): bool
    {
        foreach ($throwable->getTrace() as $frame) {
            if (($frame['class'] ?? null) === StoreConfig::class && ($frame['function'] ?? null) === 'save') {
                return true;
            }
        }

        return false;
    }
}
