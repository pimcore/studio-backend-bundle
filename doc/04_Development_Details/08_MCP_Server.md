---
title: MCP Server Infrastructure
description: Shared infrastructure for Model Context Protocol servers including firewall, authentication, and PSR-7 bridge.
---

# MCP Server Infrastructure (Experimental)

Pimcore Studio Backend provides shared infrastructure for [Model Context Protocol](https://modelcontextprotocol.io/) (MCP)
servers across bundles. This includes a dedicated security firewall, PSR-7/PSR-17 bridge services, and a dual authentication
system supporting both internal agent use and external MCP clients.

## Architecture Overview

### The `pimcore_mcp` Firewall

All MCP endpoints use the URL prefix `/pimcore-mcp/` and are protected by a dedicated Symfony firewall (`pimcore_mcp`). This
firewall is **stateless**  - each request authenticates independently, with no session migration or security token persistence.
It is separate from the `pimcore_studio` firewall to provide security isolation  - MCP authentication cannot leak to Studio
Backend API routes and vice versa.

The firewall supports two authenticators tried in order:

| Authenticator                | Trigger                  | Use Case                                                  |
|------------------------------|--------------------------|-----------------------------------------------------------|
| `SessionBridgeAuthenticator` | Session cookie           | Internal: agent-server forwarding Pimcore Studio sessions |
| `PatAuthenticator`           | `Authorization: Bearer`  | External: MCP clients (Claude Desktop, Cursor, etc.)      |

Both authenticators resolve to a Pimcore `User` object. All existing Pimcore permissions (workspace ACLs, user/role
permissions) apply automatically.

### SessionBridgeAuthenticator

The `SessionBridgeAuthenticator` authenticates MCP requests against an existing Pimcore Studio session. When the Pimcore AI
agent-server receives a request from the Studio UI, it forwards the browser's `PHPSESSID` cookie to the MCP endpoint. The
authenticator reads `_security_pimcore_admin` from the PHP session (cross-context) via the `AuthenticationResolverInterface`
to resolve the authenticated Pimcore user. It validates that the user exists and is active before creating a
`SelfValidatingPassport`.

This authenticator returns `null` on failure (rather than an error response), allowing the next authenticator in the chain
(PAT) to try.

### PSR-7/PSR-17 Bridge Services

Studio Backend Bundle provides the PSR-7/PSR-17 bridge services required by MCP controllers globally. Bundles that implement
MCP servers do not need to register these services themselves:

- `Psr\Http\Message\ResponseFactoryInterface`
- `Psr\Http\Message\StreamFactoryInterface`
- `Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface`
- `Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface`

These are defined in `config/mcp.yaml` and available for autowiring in any bundle.

## Authentication

### Session Bridge (Internal)

When the Pimcore AI agent-server receives a request from the Studio UI, it forwards the browser's `PHPSESSID` cookie to the
MCP endpoint. The `SessionBridgeAuthenticator` calls `AuthenticationResolverInterface::authenticateSession()` to read the
`_security_pimcore_admin` token from the PHP session, resolving the Pimcore user who is logged into Studio.

This authenticator returns `null` on failure (rather than an error response), allowing the next authenticator (PAT) to try.

### Personal Access Tokens (External)

External MCP clients authenticate with bearer tokens configured in YAML:

```yaml
# config/config.yaml or config/packages/pimcore_studio_backend.yaml
pimcore_studio_backend:
    mcp:
        authentication:
            tokens:
                admin:
                    - '%env(MCP_TOKEN_ADMIN)%'
                editor_user:
                    - '%env(MCP_TOKEN_EDITOR)%'
```

Each key is a Pimcore username, and the value is a list of accepted tokens for that user. Tokens can reference environment
variables to keep secrets out of YAML files.

The `PatAuthenticator` extracts the bearer token from the `Authorization` header, looks up the username in the token map,
loads the Pimcore `User`, validates it is active, and creates a `SelfValidatingPassport`.

**Client configuration example (Claude Desktop / Cursor):**

```json
{
  "mcpServers": {
    "pimcore": {
      "url": "https://your-pimcore.com/pimcore-mcp/agent/pimcore-data-objects-read",
      "headers": {
        "Authorization": "Bearer <your-token>"
      }
    }
  }
}
```

### How Permissions Work

PATs grant full access as the mapped Pimcore user. There are no MCP-specific scopes  - fine-grained restrictions use existing
Pimcore workspace and user permissions. If a user cannot edit a data object via the admin UI, they cannot edit it via MCP
tools either.

## Configuration Reference

```yaml
pimcore_studio_backend:
    mcp:
        authentication:
            tokens:
                # Map: Pimcore username => list of bearer tokens
                <username>:
                    - '<token-string-or-env-ref>'
```

The firewall is automatically configured by the bundle extension. To enable it, add the following to your
`config/packages/security.yaml` (see also [Installation](./00_Installation.md)):

```yaml
security:
    firewalls:
        pimcore_mcp: '%pimcore_studio_backend.mcp_firewall_settings%'
    access_control:
        - { path: ^/pimcore-mcp/, roles: ROLE_PIMCORE_USER }
```

No manual firewall configuration beyond this is needed  - the parameter contains the full firewall definition including the
authenticator chain, user provider, and stateless flag.

## Implementing an MCP Server in a Bundle

### Step 1: Create MCP Tool Classes

Use the `mcp/sdk` package attributes to define tools:

```php
use Mcp\Attribute\McpTool;
use Mcp\Attribute\Schema;
use Mcp\Types\CallToolResult;
use Mcp\Types\TextContent;

final readonly class MyTool
{
    #[McpTool(
        name: 'my_tool_name',
        description: 'What this tool does'
    )]
    public function execute(
        #[Schema(description: 'Parameter description')]
        string $param
    ): CallToolResult {
        // Tool implementation
        return new CallToolResult(
            [new TextContent('Result')]
        );
    }
}
```

### Step 2: Register Tools as Services

```yaml
# config/services.yaml (in your bundle)
services:
    My\Bundle\Mcp\Tool\MyTool: ~
```

### Step 3: Create the MCP Server

Build a server using the SDK, referencing your tool classes:

```php
use Mcp\Server;
use Mcp\ServerBuilder;

$builder = new ServerBuilder('my-bundle-mcp', '1.0.0');
$builder->addTool([MyTool::class, 'execute']);
$server = $builder->build();
```

### Step 4: Create Controller

Route the controller under `/pimcore-mcp/<bundle-name>`:

```php
use Mcp\Server;
use Mcp\Server\Transport\StreamableHttpTransport;
use Symfony\Component\Routing\Attribute\Route;

final readonly class McpController
{
    public function __construct(
        private Server $server,
        private HttpMessageFactoryInterface $httpMessageFactory,
        private HttpFoundationFactoryInterface $httpFoundationFactory,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory
    ) {}

    #[Route(
        path: '/pimcore-mcp/my-bundle',
        name: 'my_bundle_mcp',
        methods: ['POST', 'GET']
    )]
    public function handle(Request $request): Response
    {
        $transport = new StreamableHttpTransport(
            $this->httpMessageFactory->createRequest($request),
            $this->responseFactory,
            $this->streamFactory
        );

        return $this->httpFoundationFactory->createResponse(
            $this->server->run($transport)
        );
    }
}
```

The `pimcore_mcp` firewall automatically handles authentication for any route matching `^/pimcore-mcp/`. No custom auth code
is needed in your bundle.

### Step 5: Get the Current User (Optional)

If your tools need the authenticated user, inject `TokenStorageInterface`:

```php
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class MyTool
{
    public function __construct(
        private TokenStorageInterface $tokenStorage
    ) {}

    #[McpTool(name: 'my_tool', description: '...')]
    public function execute(): CallToolResult
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        // $user is Pimcore\Security\User\User
        // $user->getUser() returns Pimcore\Model\User
    }
}
```

Or use `SecurityServiceInterface::getCurrentUser()` which works with both session and PAT auth.
