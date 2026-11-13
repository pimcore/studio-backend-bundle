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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Security;

use const JSON_THROW_ON_ERROR;
use JsonException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Exception\InsufficientMcpScopeException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\McpScopes;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use function array_is_list;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function trim;

/**
 * @internal
 */
final readonly class McpToolCallScopeGuard implements McpToolCallScopeGuardInterface
{
    private const string METHOD_TOOLS_CALL = 'tools/call';

    public function __construct(
        private McpToolRegistryInterface $toolRegistry,
    ) {
    }

    public function assertAllowed(string $requestBody, ?array $grantedScopes): void
    {
        if ($grantedScopes === null || $grantedScopes === [] || in_array(McpScopes::WRITE, $grantedScopes, true)) {
            return;
        }

        $messages = $this->messages($requestBody);
        if ($messages === null) {
            throw InsufficientMcpScopeException::forUnreadableRequest(McpScopes::WRITE);
        }

        foreach ($messages as $message) {
            $tool = $this->calledTool($message);
            if ($tool !== null && $this->toolRegistry->get($tool)?->isReadOnly() === false) {
                throw InsufficientMcpScopeException::forTool($tool, McpScopes::WRITE);
            }
        }
    }

    /**
     * A request without a body (the GET and DELETE of the streamable transport) calls no tool. A body that is not a
     * JSON-RPC object or batch cannot be checked and is refused for a limited token: the transport would not run
     * it as a tool call either, so nothing legitimate is lost.
     *
     * @return list<mixed>|null null when the body is present but cannot be read as JSON-RPC
     */
    private function messages(string $requestBody): ?array
    {
        if (trim($requestBody) === '') {
            return [];
        }

        try {
            $decoded = json_decode($requestBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        return array_is_list($decoded) ? $decoded : [$decoded];
    }

    private function calledTool(mixed $message): ?string
    {
        if (!is_array($message) || ($message['method'] ?? null) !== self::METHOD_TOOLS_CALL) {
            return null;
        }

        $params = $message['params'] ?? null;
        $name = is_array($params) ? ($params['name'] ?? null) : null;

        return is_string($name) ? $name : null;
    }
}
