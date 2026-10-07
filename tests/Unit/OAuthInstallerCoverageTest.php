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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthClientRecord;
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthTokenRecord;
use function file_get_contents;
use function preg_match;
use function sprintf;
use function str_contains;

/**
 * The installer builds a fresh installation's schema and the migrations upgrade an existing
 * one, so a table this bundle owns has to appear in both. The OAuth tables shipped with only
 * the migration half, which left a fresh install without them until someone ran
 * `doctrine:migrations:migrate` by hand.
 *
 * Reading the installer's source is a weaker check than installing into a real database, but
 * this bundle has no database-backed suite to put that in. It does catch the regression that
 * happened: a builder that is written and never called, or one that stops declaring a column a
 * later migration added, so a fresh install and an upgraded install drift apart.
 *
 * @internal
 */
final class OAuthInstallerCoverageTest extends Unit
{
    private const string INSTALLER = __DIR__ . '/../../src/Installer.php';

    /**
     * Columns that arrive by ALTER on the upgrade path. A fresh install has to land on the
     * final shape, not on what the table-creating migration left behind.
     *
     * @var array<string, string>
     */
    private const array LATER_COLUMNS = [
        'resource' => 'Version20260901120000',
        'metadata_hash' => 'Version20260914120000',
    ];

    /**
     * The installer names its tables through the entity constant rather than the literal, so
     * that is what the source is searched for.
     *
     * @return array<string, array{string, string}>
     */
    public function oauthTableProvider(): array
    {
        return [
            'token table' => ['OAuthTokenRecord', OAuthTokenRecord::TABLE_NAME],
            'client table' => ['OAuthClientRecord', OAuthClientRecord::TABLE_NAME],
        ];
    }

    /**
     * @dataProvider oauthTableProvider
     */
    public function testTheInstallerCreatesAndDropsTheTable(string $entity, string $table): void
    {
        $source = (string) file_get_contents(self::INSTALLER);
        $reference = $entity . '::TABLE_NAME';

        $this->assertTrue(
            str_contains($source, $reference),
            sprintf('Installer.php never mentions %s (%s), so a fresh install would not create it.', $reference, $table),
        );

        $this->assertSame(
            1,
            preg_match('/dropTable\(' . preg_quote($reference, '/') . '\)/u', $source),
            sprintf('uninstall() must drop %s, matching how the other owned tables behave.', $table),
        );
    }

    public function testTheBuildersAreActuallyCalled(): void
    {
        $source = (string) file_get_contents(self::INSTALLER);

        foreach (['createOAuthTokenTable', 'createOAuthClientTable'] as $builder) {
            $this->assertSame(
                1,
                preg_match('/\$this->' . $builder . '\(\$schema\);/u', $source),
                sprintf('%s() exists but install() never calls it.', $builder),
            );
        }
    }

    public function testTheBuildersDeclareColumnsAddedByLaterMigrations(): void
    {
        $source = (string) file_get_contents(self::INSTALLER);

        foreach (self::LATER_COLUMNS as $column => $migration) {
            $this->assertTrue(
                str_contains($source, sprintf("addColumn('%s'", $column)),
                sprintf(
                    'The installer must declare %s, which %s adds to an existing installation. '
                    . 'Without it a fresh install and an upgraded install have different schemas.',
                    $column,
                    $migration,
                ),
            );
        }
    }
}
