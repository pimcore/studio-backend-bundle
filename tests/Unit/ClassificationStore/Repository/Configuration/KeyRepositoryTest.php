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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
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

    private ?KernelInterface $kernelBeforeStub = null;

    /**
     * An empty container makes `save()` give up at `Pimcore\Db::get()`, with an exception the
     * repository converts, once it has written the model.
     *
     * @throws Exception
     */
    public function _before(): void
    {
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
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $this->makeEmpty(Connection::class),
        );
    }
}
