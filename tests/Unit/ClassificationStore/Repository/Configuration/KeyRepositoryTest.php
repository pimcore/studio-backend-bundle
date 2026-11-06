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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactKeyEntry;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;
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

        $this->write(static fn (): KeyConfig => $repository->update(
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

        $this->write(static fn (): KeyConfig => $repository->update(
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

        $this->write(static fn (): KeyConfig => $repository->update(
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

        $this->write(static fn (): KeyConfig => $repository->update(
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

        $this->write(static fn (): KeyConfig => $repository->update(
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

        $this->write(static fn (): KeyConfig => $repository->update(
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

        $this->write(static fn (): KeyConfig => $repository->saveFromArtifact(
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
    private function createRepository(KeyConfig $config): KeyRepository
    {
        return new KeyRepository(
            $this->makeEmpty(KeyConfigResolverInterface::class, [
                'getById' => $config,
            ]),
            $this->keyLocalizationResolver,
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $this->makeEmpty(Connection::class),
        );
    }
}
