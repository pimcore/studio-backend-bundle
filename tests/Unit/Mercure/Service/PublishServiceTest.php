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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mercure\Service;

use Codeception\Test\Unit;
use LogicException;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishService;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UrlServiceInterface;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TypeError;

final class PublishServiceTest extends Unit
{
    /**
     * Nothing listens on port 1, so the hub request fails at the transport level.
     */
    private const string UNREACHABLE_HUB_URL = 'http://127.0.0.1:1/.well-known/mercure';

    /**
     * Only has to look like a JWT, the hub is never reached.
     */
    private const string JWT = 'header.payload.signature';

    /**
     * Publishing runs inside Doctrine lifecycle listeners, i.e. inside an open transaction. An exception that
     * escapes from here makes `UnitOfWork::commit()` roll back and close the EntityManager, so an unreachable
     * hub must never surface as an exception.
     */
    public function testUnreachableHubDoesNotThrow(): void
    {
        $errors = [];
        $service = $this->createService($this->createLogger($errors), self::UNREACHABLE_HUB_URL);

        $service->publish('topic', ['state' => 'finished']);

        $this->assertCount(1, $errors);
    }

    public function testUnreachableHubFailureIsLoggedWithTopicsAndException(): void
    {
        $errors = [];
        $service = $this->createService($this->createLogger($errors), self::UNREACHABLE_HUB_URL);

        $service->publish(['topic-a', 'topic-b'], ['state' => 'finished']);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Failed to publish Mercure update', $errors[0]['message']);
        $this->assertSame(['topic-a', 'topic-b'], $errors[0]['context']['topics']);
        $this->assertInstanceOf(RuntimeException::class, $errors[0]['context']['exception']);
    }

    public function testStringTopicIsLoggedAsList(): void
    {
        $errors = [];
        $service = $this->createService($this->createLogger($errors), self::UNREACHABLE_HUB_URL);

        $service->publish('single-topic', ['state' => 'finished']);

        $this->assertSame(['single-topic'], $errors[0]['context']['topics']);
    }

    public function testMissingHubConfigurationDoesNotThrow(): void
    {
        $errors = [];
        $service = new PublishService(
            $this->createLogger($errors),
            $this->makeEmpty(SerializerInterface::class, ['serialize' => '{}']),
            $this->makeEmpty(TokenProviderInterface::class, ['getJwt' => self::JWT]),
            $this->makeEmpty(UrlServiceInterface::class, [
                'getServerSideUrl' => static fn () => throw new LogicException('Mercure server URL is not configured.'),
            ]),
        );

        $service->publish('topic', ['state' => 'finished']);

        $this->assertCount(1, $errors);
        $this->assertInstanceOf(LogicException::class, $errors[0]['context']['exception']);
    }

    /**
     * The serialization of the payload is not part of the transport. A payload that cannot be serialized is a
     * programming error and must stay visible to the caller.
     */
    public function testSerializationFailureStillPropagates(): void
    {
        $errors = [];
        $service = new PublishService(
            $this->createLogger($errors),
            $this->makeEmpty(SerializerInterface::class, [
                'serialize' => static fn () => throw new LogicException('cannot serialize'),
            ]),
            $this->makeEmpty(TokenProviderInterface::class, ['getJwt' => self::JWT]),
            $this->makeEmpty(UrlServiceInterface::class, ['getServerSideUrl' => self::UNREACHABLE_HUB_URL]),
        );

        try {
            $service->publish('topic', ['state' => 'finished']);
            $this->fail('The serialization failure must reach the caller.');
        } catch (LogicException $exception) {
            $this->assertSame('cannot serialize', $exception->getMessage());
        }

        $this->assertSame([], $errors);
    }

    /**
     * Only exceptions are treated as a delivery failure. A PHP error (e.g. a TypeError from a misbehaving token
     * provider) is a programming error and must not be swallowed.
     */
    public function testPhpErrorsStillPropagate(): void
    {
        $errors = [];
        $service = new PublishService(
            $this->createLogger($errors),
            $this->makeEmpty(SerializerInterface::class, ['serialize' => '{}']),
            $this->makeEmpty(TokenProviderInterface::class, [
                'getJwt' => static fn () => throw new TypeError('broken token provider'),
            ]),
            $this->makeEmpty(UrlServiceInterface::class, ['getServerSideUrl' => self::UNREACHABLE_HUB_URL]),
        );

        try {
            $service->publish('topic', ['state' => 'finished']);
            $this->fail('The PHP error must reach the caller.');
        } catch (TypeError $error) {
            $this->assertSame('broken token provider', $error->getMessage());
        }

        $this->assertSame([], $errors);
    }

    private function createService(LoggerInterface $logger, string $hubUrl): PublishService
    {
        return new PublishService(
            $logger,
            $this->makeEmpty(SerializerInterface::class, ['serialize' => '{"state":"finished"}']),
            $this->makeEmpty(TokenProviderInterface::class, ['getJwt' => self::JWT]),
            $this->makeEmpty(UrlServiceInterface::class, ['getServerSideUrl' => $hubUrl]),
        );
    }

    /**
     * @param array<int, array{message: string, context: array<string, mixed>}> $errors collects every error() call
     */
    private function createLogger(array &$errors): LoggerInterface
    {
        return $this->makeEmpty(LoggerInterface::class, [
            'error' => static function (string|Stringable $message, array $context = []) use (&$errors): void {
                $errors[] = ['message' => (string)$message, 'context' => $context];
            },
        ]);
    }
}
