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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Gdpr\Provider;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Email\Repository\EmailLogRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\SentMailProvider;
use Pimcore\Model\Tool\Email\Log;
use Pimcore\Model\Tool\Email\Log\Listing;

final class SentMailProviderTest extends Unit
{
    public function testFindDataIncludesLogAndErrorFlags(): void
    {
        $log = $this->makeEmpty(Log::class, [
            'getId' => 42,
            'getObjectVars' => ['from' => 'from@example.com', 'subject' => 'Failed message'],
            'getTo' => 'to@example.com',
            'getCc' => null,
            'getBcc' => null,
            'getSentDate' => 1716755895,
            'getEmailLogExistsHtml' => 1,
            'getEmailLogExistsText' => 1,
            'getError' => 'Delivery failed',
            'getParams' => [],
        ]);
        $listing = new class($log) extends Listing {
            public function __construct(private readonly Log $log)
            {
            }

            public function load(): array
            {
                return [$this->log];
            }

            public function getTotalCount(): int
            {
                return 1;
            }
        };
        $repository = $this->makeEmpty(EmailLogRepositoryInterface::class, [
            'getFilteredListing' => $listing,
        ]);

        $result = (new SentMailProvider($repository))->findData(new FilterParameter());
        $data = $result->getItems()[0]->getData();

        $this->assertTrue($data['hasHtmlLog']);
        $this->assertTrue($data['hasTextLog']);
        $this->assertTrue($data['hasError']);
        $this->assertSame('Delivery failed', $data['error']);
    }
}
