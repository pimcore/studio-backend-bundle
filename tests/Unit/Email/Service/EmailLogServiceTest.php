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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Email\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Email\Repository\EmailLogRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Email\Service\EmailLogService;
use Pimcore\Bundle\StudioBackendBundle\Email\Service\MailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Model\Tool\Email\Log;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EmailLogServiceTest extends Unit
{
    public function testGetEntryParamsNormalizesNonStringValues(): void
    {
        $emailLog = $this->makeEmpty(Log::class, [
            'getParams' => [
                ['key' => 'bool', 'data' => ['type' => 'bool', 'value' => true]],
                ['key' => 'int', 'data' => ['type' => 'int', 'value' => 118]],
                ['key' => 'array', 'data' => ['type' => 'array', 'value' => ['foo' => 'bar']]],
            ],
        ]);
        $repository = $this->makeEmpty(EmailLogRepositoryInterface::class, [
            'getExistingEntry' => $emailLog,
        ]);
        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => static fn (object $event): object => $event,
        ]);
        $service = new EmailLogService(
            $this->makeEmpty(DocumentResolverInterface::class),
            $repository,
            $eventDispatcher,
            $this->makeEmpty(MailServiceInterface::class),
            $this->makeEmpty(TranslatorServiceInterface::class),
        );

        $params = $service->getEntryParams(1);

        $this->assertSame('true', $params[0]->getValue());
        $this->assertSame('118', $params[1]->getValue());
        $this->assertSame('{"foo":"bar"}', $params[2]->getValue());
    }
}
