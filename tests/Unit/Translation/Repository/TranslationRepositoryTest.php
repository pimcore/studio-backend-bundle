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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Translation\Repository;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SettingsProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepository;
use Pimcore\Config;
use Pimcore\Model\Translation\Listing;
use Pimcore\Model\Translation\Listing\Dao as ListingDao;

/**
 * @internal
 */
final class TranslationRepositoryTest extends Unit
{
    /**
     * Every joined language column is a self-join of the translations table with its own `key` and `type`
     * columns, so sorting by them must reference the main table (pimcore/platform-version#599).
     *
     * @throws Exception
     */
    public function testQualifyMainTableOrderKeysQualifiesKeyAndTypeOnly(): void
    {
        $listingDao = $this->createMock(ListingDao::class);
        $listingDao->method('getDatabaseTableName')->willReturn('translations_messages');
        $listing = $this->createMock(Listing::class);
        $listing->method('getDao')->willReturn($listingDao);
        // order keys as quoted by the core listing after the sort filter was applied
        $listing->method('getOrderKey')->willReturn(['`type`', '`key`', '`de`']);
        $listing->expects($this->once())
            ->method('setOrderKey')
            ->with(['translations_messages.`type`', 'translations_messages.`key`', '`de`'], false);

        $this->assertSame($listing, $this->getRepository()->qualifyMainTableOrderKeys($listing));
    }

    /**
     * @throws Exception
     */
    private function getRepository(): TranslationRepository
    {
        return new TranslationRepository(
            $this->makeEmpty(SettingsProviderInterface::class, ['getSettings' => []]),
            $this->makeEmpty(Connection::class),
            $this->makeEmpty(SecurityServiceInterface::class),
            new Config(),
        );
    }
}
