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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Email\Controller;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Email\Controller\SearchController;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\EmailLogEntry;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\GdprManagerServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use ReflectionMethod;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class SearchControllerTest extends Unit
{
    public function testSearchRequiresEmailsPermission(): void
    {
        $method = new ReflectionMethod(SearchController::class, 'search');
        $attributes = $method->getAttributes(IsGranted::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(UserPermissions::EMAILS->value, $attributes[0]->newInstance()->attribute);
    }

    public function testSearchReturnsPaginatedEmailLogEntries(): void
    {
        $serializedCollection = null;
        $providerKey = null;
        $serializer = $this->makeEmpty(SerializerInterface::class, [
            'serialize' => static function (Collection $collection) use (&$serializedCollection): string {
                $serializedCollection = $collection;

                return '{"totalItems":7,"items":[]}';
            },
        ]);
        $manager = $this->makeEmpty(GdprManagerServiceInterface::class, [
            'search' => static function (
                CollectionFilterParameter $parameters,
                string $provider
            ) use (&$providerKey): Collection {
                $providerKey = $provider;

                return new Collection(7, [new GdprDataRow([
                    'id' => 42,
                    'sentDate' => 1716755895,
                    'hasHtmlLog' => true,
                    'hasTextLog' => true,
                    'hasError' => true,
                    'from' => 'from@example.com',
                    'to' => 'to@example.com',
                    'subject' => 'Failed message',
                ])]);
            },
        ]);

        $response = (new SearchController($serializer, $manager))->search(new CollectionFilterParameter());

        $this->assertSame('7', $response->headers->get('X-Pimcore-Total-Items'));
        $this->assertSame('sent_mails', $providerKey);
        $this->assertInstanceOf(Collection::class, $serializedCollection);
        $this->assertSame(7, $serializedCollection->getTotalItems());
        $entry = $serializedCollection->getItems()[0];
        $this->assertInstanceOf(EmailLogEntry::class, $entry);
        $this->assertSame(42, $entry->getId());
        $this->assertTrue($entry->getHasHtmlLog());
        $this->assertTrue($entry->getHasTextLog());
        $this->assertTrue($entry->getHasError());
    }

    public function testSearchPropagatesProviderException(): void
    {
        $manager = $this->makeEmpty(GdprManagerServiceInterface::class, [
            'search' => static function (): never {
                throw new NotFoundException('GDPR provider', 'sent_mails');
            },
        ]);
        $controller = new SearchController($this->makeEmpty(SerializerInterface::class), $manager);

        $this->expectException(NotFoundException::class);

        $controller->search(new CollectionFilterParameter());
    }
}
