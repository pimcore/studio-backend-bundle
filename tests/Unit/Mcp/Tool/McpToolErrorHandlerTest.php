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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Tool;

use Codeception\Test\Unit;
use Doctrine\DBAL\Exception as DbalException;
use InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Exception\InvalidMcpToolArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolErrorHandler;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use function json_encode;
use function preg_match;

/**
 * MCP tool results leave the Pimcore boundary: external clients forward them to whichever
 * third-party model they are wired to.
 *
 * The contract is deliberately blunt: an exception reaching the handler never has its message
 * forwarded, with one exception whose safety is a property of the type rather than of a call site.
 *
 * @internal
 */
final class McpToolErrorHandlerTest extends Unit
{
    private const string TOOL = 'update_document';

    /**
     * The one forwarded type. Throwing it states that the message was composed for the caller.
     */
    public function testArgumentExceptionMessageIsForwarded(): void
    {
        $message = $this->handler(new RecordingLogger())->handle(
            new InvalidMcpToolArgumentException('"data" must be an object, not a list.'),
            self::TOOL,
        );

        $this->assertSame('"data" must be an object, not a list.', $message);
    }

    /**
     * Notice, not error, and no trace: nothing failed server-side.
     */
    public function testArgumentExceptionIsLoggedAtNoticeWithoutTrace(): void
    {
        $logger = new RecordingLogger();

        $this->handler($logger)->handle(
            new InvalidMcpToolArgumentException('"editables" must be an object.'),
            self::TOOL,
            ['id' => 42],
        );

        $this->assertCount(1, $logger->records);
        $this->assertSame('notice', $logger->records[0]['level']);
        $this->assertArrayNotHasKey('trace', $logger->records[0]['context']);
        // The tool's own structured context survives, so the operator can still correlate.
        $this->assertSame(42, $logger->records[0]['context']['id']);
    }

    public function testEmptyArgumentMessageFallsBackToARejectionSentence(): void
    {
        $this->assertSame(
            'The request was rejected.',
            $this->handler(new RecordingLogger())->handle(
                new InvalidMcpToolArgumentException(''),
                self::TOOL,
            ),
        );
    }

    /**
     * @dataProvider notForwardedProvider
     *
     * @param list<string> $forbiddenFragments
     */
    public function testNothingElseIsForwarded(Throwable $exception, array $forbiddenFragments, string $why): void
    {
        $logger = new RecordingLogger();

        $message = $this->handler($logger)->handle($exception, self::TOOL);

        // Assert against the serialized payload, which is what a tool actually ships.
        // JSON_UNESCAPED_SLASHES keeps path fragments comparable — an escaped "\/var\/www"
        // would make the assertion pass on a genuine leak.
        $payload = (string) json_encode(['error' => $message], JSON_UNESCAPED_SLASHES);

        $this->assertStringNotContainsString($exception::class, $payload, $why);
        $this->assertStringNotContainsString($exception->getMessage(), $payload, $why);
        foreach ($forbiddenFragments as $fragment) {
            $this->assertStringNotContainsString($fragment, $payload, $why);
        }

        // …while the server-side record keeps everything needed to diagnose it.
        $this->assertSame('error', $logger->records[0]['level']);
        $this->assertSame($exception, $logger->records[0]['context']['exception']);
        $this->assertSame($exception::class, $logger->records[0]['context']['exceptionClass']);
    }

    /**
     * @return array<string, array{Throwable, list<string>, string}>
     */
    public function notForwardedProvider(): array
    {
        $sql = "An exception occurred while executing 'SELECT id FROM documents_page WHERE template = ?'";

        return [
            'dbal quotes the statement' => [
                new class($sql) extends RuntimeException implements DbalException {},
                ['SELECT', 'documents_page'],
                'A DBAL message quotes the failing statement.',
            ],
            'search backend leaks the index and host' => [
                new RuntimeException('no such index [product_index-1] on node opensearch:9200'),
                ['product_index', 'opensearch:9200'],
                'A search-backend message names indices and nodes.',
            ],
            'a 5xx HttpException' => [
                new HttpException(500, 'Saving failed: SQLSTATE[HY000] deadlock on `objects`'),
                ['SQLSTATE', 'deadlock'],
                'A server fault may quote infrastructure.',
            ],
            // The reason there is no allowlist: this class is given a caller-facing literal at
            // five call sites and an inner getMessage() at CloneService and WidgetValidationService,
            // so no class-level judgement about it can be right.
            'a 422 that some call sites populate from an inner exception' => [
                new ValidationFailedException('Failed to clone document: SQLSTATE[HY000] deadlock'),
                ['SQLSTATE'],
                'Trust is a property of the construction, not of the class.',
            ],
            // 130+ library classes extend SPL InvalidArgumentException, quoting paths, DSNs and URLs.
            'plain SPL InvalidArgumentException' => [
                new InvalidArgumentException('Cache file is not writable: "/var/www/html/var/cache/x"'),
                ['/var/www/html'],
                'Extending SPL InvalidArgumentException says nothing about who wrote the message.',
            ],
            // Caller-facing in practice, and still not forwarded here: a tool that wants to say
            // "not found" composes that sentence itself, from the id it already holds.
            'even a caller-facing API exception' => [
                new NotFoundException('document', 42),
                [],
                'The handler does not distinguish; the tool type-catches when it wants the message.',
            ],
        ];
    }

    /**
     * The correlation id is what replaces a dev/prod split: it has to appear in both the returned
     * sentence and the log record, or it buys nothing.
     */
    public function testGenericMessageCarriesACorrelationIdMatchingTheLogRecord(): void
    {
        $logger = new RecordingLogger();

        $message = $this->handler($logger)->handle(new RuntimeException('kaboom'), self::TOOL);

        $this->assertSame(1, preg_match('/\(ref: ([0-9a-f]{8})\)/u', $message, $matches));
        $this->assertSame($matches[1], $logger->records[0]['context']['ref']);
        $this->assertStringContainsString($matches[1], $logger->records[0]['message']);
    }

    public function testCorrelationIdDiffersPerCall(): void
    {
        $handler = $this->handler(new RecordingLogger());

        $this->assertNotSame(
            $handler->handle(new RuntimeException('a'), self::TOOL),
            $handler->handle(new RuntimeException('b'), self::TOOL),
        );
    }

    /**
     * Passing the Throwable rather than its class name is what lets Monolog's normalizer expand
     * file, line, code and the whole `previous` chain — and the root cause of a wrapped API
     * exception lives in `previous`.
     */
    public function testThrowableItselfIsAttachedToTheLogRecord(): void
    {
        $logger = new RecordingLogger();
        $exception = new RuntimeException('kaboom');

        $this->handler($logger)->handle($exception, self::TOOL, ['id' => 7]);

        $this->assertSame($exception, $logger->records[0]['context']['exception']);
        $this->assertSame(RuntimeException::class, $logger->records[0]['context']['exceptionClass']);
        $this->assertSame(7, $logger->records[0]['context']['id']);
    }

    /**
     * The handler is the terminal boundary, so nothing inside it may throw: an exception escaping
     * here would replace the tool's original failure *and* lose it, because the log call happens
     * after the correlation id is minted. `random_bytes()` can throw on entropy exhaustion, so the
     * id is generated behind a fallback.
     *
     * Entropy failure cannot be provoked in-process, so this asserts the property that makes the
     * fallback correct: the id is produced without touching anything that can fail, and every
     * caller gets a well-formed one.
     */
    public function testCorrelationIdIsAlwaysWellFormed(): void
    {
        $logger = new RecordingLogger();
        $handler = $this->handler($logger);

        for ($i = 0; $i < 25; $i++) {
            $message = $handler->handle(new RuntimeException('kaboom'), self::TOOL);

            $this->assertSame(
                1,
                preg_match('/\(ref: [0-9a-f]{8}\)/u', $message),
                'Every correlation id must be 8 hex characters.',
            );
        }

        $this->assertCount(25, $logger->records);
    }

    private function handler(RecordingLogger $logger): McpToolErrorHandler
    {
        return new McpToolErrorHandler($logger);
    }
}

/**
 * Minimal PSR-3 recorder: these tests assert on the log *level* as well as the payload, which a
 * mock would only verify for the calls the test already spells out.
 *
 * @internal
 */
final class RecordingLogger extends AbstractLogger
{
    /**
     * @var list<array{level: string, message: string, context: array<string, mixed>}>
     */
    public array $records = [];

    /**
     * @param array<string, mixed> $context
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
    }
}
