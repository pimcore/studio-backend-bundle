---
title: Providing MCP Tools
description: Contribute MCP tools from your bundle as SDK-native #[McpTool] services; tag them to make them assignable to runtime-configured MCP servers.
---

# Providing MCP Tools

An **MCP tool** is a single capability (read a data object, run a search, …) that a bundle contributes to the
Studio Backend. A tool is an **SDK-native `#[McpTool]` service** — the same shape the `mcp/sdk` package and the
Pimcore Agent bundle use — that you opt in with a tag. Administrators then assign it to a
[runtime-configured MCP server](../04_Development_Details/09_MCP_Server_Management.md).

This is the tool-author counterpart to the lower-level "implement a whole MCP server in a bundle" recipe in
[MCP Server Infrastructure](../04_Development_Details/08_MCP_Server.md): you provide *tools*, and which servers
expose them and who may use them is configured at runtime.

> **Experimental.** The feature is under active development and may change between minor versions.

## The contract

A tool is a plain service with a public method carrying the `Mcp\Capability\Attribute\McpTool` attribute; the
method returns an `Mcp\Schema\Result\CallToolResult`. There is **no bundle-specific interface** — you use the SDK
types directly. Parameters are described with `Mcp\Capability\Attribute\Schema`, and the input JSON Schema is
inferred from them.

```php
namespace App\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Pimcore\Model\DataObject;

final class GetObjectNameTool
{
    #[McpTool(
        name: 'get_object_name',
        title: 'Get Object Name',
        description: 'Returns the key of a data object by id.',
        annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true),
    )]
    public function execute(
        #[Schema(type: 'integer', description: 'The data object id.', minimum: 1)]
        int $id,
    ): CallToolResult {
        $object = DataObject::getById($id);
        if ($object === null) {
            return new CallToolResult([new TextContent('No object with that id.')], isError: true);
        }

        return new CallToolResult([new TextContent($object->getKey())]);
    }
}
```

A service may carry more than one `#[McpTool]` method; each becomes a separate tool. The `name` defaults to the
method name and the `description` to the DocBlock summary when omitted.

## Registering the tool

Tools are collected explicitly by tag — so a bundle chooses which of its `#[McpTool]` services are exposed as
assignable studio tools:

```yaml
# config/services.yaml
services:
    App\Mcp\Tool\GetObjectNameTool:
        autowire: true
        tags: ['pimcore.studio_backend.mcp_tool']
```

A compiler pass reflects each tagged service's `#[McpTool]` methods into the tool registry and builds a service
locator the MCP server uses to resolve the backing service at call time. Requirements enforced at build time:

- a tagged service **must** expose at least one `#[McpTool]` method (otherwise the container fails to compile);
- tool `name`s must be **unique** across all tagged services.

The tool then appears in the catalogue (`GET /pimcore-studio/api/mcp/tools`) and becomes assignable to a server.

## Annotations and the required scope

`ToolAnnotations` mirrors the MCP annotation set and determines the tool's **required OAuth scope**:

| Annotation | Meaning |
|------------|---------|
| `readOnlyHint` | The tool does not modify state. **`true` ⇒ scope `mcp:read`; otherwise `mcp:write`.** |
| `destructiveHint` | The tool may perform destructive updates (hint). |
| `idempotentHint` | Repeated identical calls have no additional effect (hint). |
| `openWorldHint` | The tool interacts with the outside world (hint). |

The fail-safe default (no `readOnlyHint`, or `false`) is treated as a write. A server's advertised scopes are the
union of its tools' required scopes.

## Results and errors

Return a `CallToolResult` built from `TextContent` (or other `Mcp\Schema\Content\*` types). For a tool-level
failure the caller should see, return a result with `isError: true` and a message you are happy to expose, or
throw `Mcp\Exception\ToolCallException` (the SDK converts it to an error result). **Uncaught exceptions are
sanitized by a shared terminal error boundary** that forwards no internal exception message, so never rely on an
exception message reaching the caller.

## Related

- [MCP Server Management](../04_Development_Details/09_MCP_Server_Management.md) — assigning tools to servers,
  the permission, and the sharing model.
- [MCP Server Infrastructure](../04_Development_Details/08_MCP_Server.md) — the firewall and authentication.
