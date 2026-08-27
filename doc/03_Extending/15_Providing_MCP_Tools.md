---
title: Providing MCP Tools
description: Contribute MCP tools from your bundle by implementing a tagged interface; they become assignable to runtime-configured MCP servers.
---

# Providing MCP Tools

An **MCP tool** is a single capability (read a data object, run a search, …) that a bundle contributes to the
Studio Backend. Tools self-register: implement one interface, and your tool joins a shared registry from which
administrators assign it to a **runtime-configured MCP server** (see
[MCP Server Management](../04_Development_Details/09_MCP_Server_Management.md)).

This is the tool-author counterpart to the lower-level "implement a whole MCP server in a bundle" recipe in
[MCP Server Infrastructure](../04_Development_Details/08_MCP_Server.md): you provide *tools*, and which servers
exist and who may use them is configured at runtime rather than in code.

> **Experimental.** The tool contract may change between minor versions.

## The contract

Implement `Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolInterface`:

```php
interface McpToolInterface
{
    public function getDefinition(): McpToolDefinition;

    /**
     * @param array<string, mixed> $arguments validated against the definition's input schema
     */
    public function execute(array $arguments): McpToolResult;
}
```

- **`getDefinition()`** describes the tool: its stable id, human-facing title/description, MCP annotations, and
  the JSON Schemas for input and (optionally) output.
- **`execute()`** runs it and returns an `McpToolResult`.

## A minimal tool

```php
namespace App\Mcp\Tool;

use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolAnnotations;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolResult;

final class GetObjectNameTool implements McpToolInterface
{
    public function getDefinition(): McpToolDefinition
    {
        return new McpToolDefinition(
            name: 'get_object_name',
            title: 'Get Object Name',
            description: 'Returns the key of a data object by id.',
            annotations: new McpToolAnnotations(readOnly: true),
            inputSchema: [
                'type' => 'object',
                'properties' => ['id' => ['type' => 'integer']],
                'required' => ['id'],
                'additionalProperties' => false,
            ],
        );
    }

    public function execute(array $arguments): McpToolResult
    {
        $object = \Pimcore\Model\DataObject::getById((int) $arguments['id']);
        if ($object === null) {
            return McpToolResult::error('No object with that id.');
        }

        return McpToolResult::text($object->getKey());
    }
}
```

`McpToolResult` has two factories: `McpToolResult::text(string)` for a successful textual result and
`McpToolResult::error(string)` for a tool-level error.

## Registering the tool

Tools are collected by tag. If your bundle's services are autoconfigured, implementing `McpToolInterface` is
enough — the bundle registers the tag for autoconfiguration. To be explicit (or when autoconfiguration is off),
add the tag yourself:

```yaml
# config/services.yaml
services:
    App\Mcp\Tool\GetObjectNameTool:
        autowire: true
        tags: ['pimcore.studio_backend.mcp_tool']
```

The tagged tool appears in the tool catalogue (`GET /pimcore-studio/api/mcp/tools`) and becomes assignable to a
server. A duplicate tool `name` across the container is rejected at build time.

## Annotations and the required scope

`McpToolAnnotations` mirrors the MCP annotation set and, importantly, determines the tool's **required OAuth
scope**:

| Annotation | Meaning |
|------------|---------|
| `readOnly` | The tool does not modify state. **`readOnly: true` ⇒ scope `mcp:read`; otherwise `mcp:write`.** |
| `destructive` | The tool may perform destructive updates (hint). |
| `idempotent` | Repeated identical calls have no additional effect (hint). |
| `openWorld` | The tool interacts with the outside world (hint). |

The default (all `false`) is the fail-safe: an unannotated tool is treated as a write. A server's advertised
scopes are the union of its tools' required scopes.

## Errors leaving the boundary

Return `McpToolResult::error()` for expected, caller-facing failures with a message you are happy to expose.
Uncaught exceptions are caught by a shared terminal error boundary that forwards **no** internal exception
message to the client, so never rely on an exception message reaching the caller.

## Related

- [MCP Server Management](../04_Development_Details/09_MCP_Server_Management.md) — assigning tools to servers,
  the permission, and the sharing model.
- [MCP Server Infrastructure](../04_Development_Details/08_MCP_Server.md) — the firewall, authentication, and the
  lower-level server-in-a-bundle recipe.
