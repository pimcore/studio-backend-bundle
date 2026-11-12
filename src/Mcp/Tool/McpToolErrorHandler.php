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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Tool;

use Pimcore\Bundle\StudioBackendBundle\Mcp\Exception\InvalidMcpToolArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;
use function bin2hex;
use function hash;
use function hrtime;
use function random_bytes;
use function sprintf;
use function substr;

/**
 * Default {@see McpToolErrorHandlerInterface}: the terminal `catch` of an MCP tool.
 *
 * MCP tool results do not stay inside Pimcore. The built-in MCP servers are consumed by external
 * clients (Claude Desktop, Cursor, ...) which forward tool output to whichever third-party model
 * they are wired to, so a raw `$e->getMessage()` carries DBAL/SQL, OpenSearch and Twig internals
 * across the Pimcore boundary.
 *
 * ## The rule
 *
 * **An exception that reaches here never has its message forwarded.** The terminal catch is the
 * "I did not anticipate this" branch, and a message you did not anticipate is not one you can
 * vouch for. The client gets a generic sentence naming the tool and a correlation id; the operator
 * gets the exception, in full, in the log.
 *
 * A tool that *can* say something useful about a failure should type-catch it and compose that
 * sentence itself, out of values it holds. `Document 42 not found. Use search_documents to find
 * valid ids.` is both safer and better copy than any exception message.
 *
 * ## Why there is no allowlist of "safe" exception types
 *
 * Because safety is a property of the *construction*, not of the class. An exception whose
 * constructor composes its message from typed scalars carries a real invariant; one that takes a
 * free-form `string $message` does not, because the answer belongs to whoever calls it. In this
 * bundle alone, `ValidationFailedException` is given a literal written for the caller at five call
 * sites and an inner `getMessage()` at `Document\Service\ExecutionEngine\CloneService` and
 * `Perspective\Service\WidgetValidationService`. Any class-level marking of it is wrong half the
 * time.
 *
 * The corollary for callers: where a foreign message really is worth forwarding, type-catch it at
 * the tool that understands why, and say why at the catch.
 *
 * ## The one forwarded type
 *
 * {@see InvalidMcpToolArgumentException} is returned verbatim, because throwing it is an explicit
 * statement that the message was composed for the caller. See that class for the contract.
 *
 * ## Why there is no dev/prod split
 *
 * A tool's failure is not a Symfony error response. Tools catch their own exceptions and return
 * the text as *application data* inside an HTTP 200 JSON-RPC result, so `kernel.debug`,
 * `ErrorListener` and the prod/dev error rendering never see it. Gating disclosure on the
 * environment would not inherit a framework behaviour, it would invent one, and it would turn
 * "nothing leaks" from an invariant a test can assert unconditionally into one that holds in a
 * single environment, on boxes that still talk to real third-party model providers. The
 * correlation id gives the same debugging benefit everywhere: grep the application log for the ref.
 *
 * The handler returns a *message*, not a result envelope, because tools differ in the envelope
 * they return and each keeps its own.
 *
 * @internal depend on {@see McpToolErrorHandlerInterface}, which is the supported contract
 */
final readonly class McpToolErrorHandler implements McpToolErrorHandlerInterface
{
    /**
     * Bytes of entropy behind a correlation id; rendered as twice as many hex characters.
     */
    private const int REF_BYTES = 4;

    private const string CODE_BAD_ARGUMENT = 'invalid_argument';

    private const string CODE_INTERNAL = 'internal_error';

    private const string MSG_INTERNAL = 'Internal error while executing %s (ref: %s). '
        . 'The cause was written to the Pimcore application log.';

    /**
     * Fallback for an argument rejection that carries no message of its own.
     */
    private const string MSG_REJECTED = 'The request was rejected.';

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function handle(Throwable $exception, string $toolName, array $context = []): string
    {
        if ($exception instanceof InvalidMcpToolArgumentException) {
            return $this->handleBadArgument($exception, $toolName, $context);
        }

        return $this->handleUnexpected($exception, $toolName, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function handleBadArgument(Throwable $exception, string $toolName, array $context): string
    {
        $message = $exception->getMessage();

        // Notice rather than error, and no stack trace: nothing failed server-side.
        $this->logger->notice(
            sprintf('%s rejected the request: %s', $toolName, $message),
            ['tool' => $toolName, 'code' => self::CODE_BAD_ARGUMENT] + $context
        );

        return $message !== '' ? $message : self::MSG_REJECTED;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function handleUnexpected(Throwable $exception, string $toolName, array $context): string
    {
        $ref = $this->correlationId();

        $this->logger->error(
            sprintf('Unhandled exception in %s (ref: %s)', $toolName, $ref),
            [
                'tool' => $toolName,
                'code' => self::CODE_INTERNAL,
                'ref' => $ref,
                // The Throwable itself, per PSR-3: Monolog's normalizer expands it to class,
                // message, code, file, line and the whole `previous` chain. Passing the class name
                // as a string instead would drop all of that, and the root cause of a wrapped
                // API exception lives in `previous`.
                'exception' => $exception,
                'exceptionClass' => $exception::class,
            ] + $context
        );

        return sprintf(self::MSG_INTERNAL, $toolName, $ref);
    }

    /**
     * A short token shared between the returned sentence and the log record, so an operator can grep
     * one to find the other.
     *
     * It is not a secret and carries no authority: it only has to be unique enough to grep within a
     * log file. `random_bytes()` is used because it is the source that does not raise Sonar's
     * weak-randomness hotspot, but it can throw when the system runs out of entropy — and this is
     * the terminal error boundary, so a throw here would lose the very exception the handler exists
     * to record, and escape in its place. The fallback keeps that impossible.
     */
    private function correlationId(): string
    {
        try {
            return bin2hex(random_bytes(self::REF_BYTES));
        } catch (Throwable) {
            // Monotonic, non-blocking and cannot throw. Collisions are irrelevant: neighbouring
            // records are distinguished by the exception and context logged alongside the ref.
            return substr(hash('xxh128', (string) hrtime(true)), 0, self::REF_BYTES * 2);
        }
    }
}
