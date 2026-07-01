---
title: MCP Server Infrastructure
description: Shared infrastructure for Model Context Protocol servers including firewall, authentication, and PSR-7 bridge.
---

# MCP Server Infrastructure (Experimental)

Pimcore Studio Backend provides shared infrastructure for [Model Context Protocol](https://modelcontextprotocol.io/) (MCP)
servers across bundles. This includes a dedicated security firewall, PSR-7/PSR-17 bridge services, and a multi-authenticator
security system supporting both internal agent use and external MCP clients.

## The `pimcore_mcp` Firewall

All MCP endpoints use the URL prefix `/pimcore-mcp/` and are protected by a dedicated Symfony firewall (`pimcore_mcp`). This
firewall is **stateless** - each request authenticates independently, with no session migration or security token persistence.
It is separate from the `pimcore_studio` firewall to provide security isolation - MCP authentication cannot leak to Studio
Backend API routes and vice versa.

All authenticators resolve to a Pimcore `User` object, and all existing Pimcore permissions (workspace ACLs, user/role
permissions) apply automatically. There are no MCP-specific scopes: if a user cannot edit a data object via the admin UI,
they cannot edit it via MCP tools either.

### Who calls MCP endpoints, and with which credential

| Caller | Credential | Authenticator |
|--------|------------|---------------|
| Pimcore AI agent-server (internal, per chat session) | `Authorization: Bearer pmcp_…` | `McpAccessTokenAuthenticator` |
| External MCP client (Claude Desktop, Cursor, …) | `Authorization: Bearer <static PAT>` | `PatAuthenticator` |
| Legacy / session-bridged internal call | Pimcore session cookie (`PHPSESSID`) | `SessionBridgeAuthenticator` |

The **primary internal path is the MCP access token** (`Bearer pmcp_…`). The Pimcore AI agent-server mints one dynamic,
expiring, revocable token per chat session and sends it on every `/pimcore-mcp/` request. Earlier versions forwarded the
browser's `PHPSESSID` cookie instead; the agent-server no longer does this for MCP calls (a bearer token survives browser
session expiry during long-running agent runs and binds the request to a specific chat session). `SessionBridgeAuthenticator`
remains registered for backward compatibility and for any caller that still presents a Pimcore Studio session cookie, but it
is no longer the agent-server's MCP mechanism.

### Authenticator chain

The firewall tries these authenticators in order; each returns `null` on failure so the next one can try:

| Order | Authenticator | Trigger | Use case |
|-------|---------------|---------|----------|
| 1 | `SessionBridgeAuthenticator` | Pimcore session cookie present | Legacy / callers that forward a Studio session |
| 2 | `McpAccessTokenAuthenticator` | `Authorization: Bearer pmcp_…` | Internal: dynamically-issued, expiring, revocable per-chat-session tokens (Pimcore AI agent) |
| 3 | `PatAuthenticator` | `Authorization: Bearer <other>` | External: MCP clients (Claude Desktop, Cursor, etc.) |

### `McpAccessTokenAuthenticator` (primary internal)

Validates a `pmcp_`-prefixed bearer token via `McpAccessTokenService` (DB-backed, hashed at rest, TTL-bounded, revocable).
It **never reads the PHP session**. On success, the validated token's `reference` (the chat session id) is stashed on the
request attributes (`_mcp_token_reference`) so trusted downstream code can use it instead of any forge-able header. On
failure it returns `null`, so the firewall falls through to `PatAuthenticator`.

The studio-backend bundle owns both validation (`McpAccessTokenAuthenticator`) and the issuance/refresh/revoke primitives
(`McpAccessTokenServiceInterface` → `McpAccessTokenService`, hashed-at-rest persistence via `McpAccessTokenRepository`,
GC via the `studio_mcp_access_token_gc` maintenance task). Consuming bundles (e.g. `pimcore-agent-bundle`) layer their own
policy on top - *when* to mint a fresh token vs. extend an existing one, which `reference` to bind it to (typically a chat
session id), and what TTL to apply - by calling `issue()` / `refresh()` / `revoke()` on the injected service.

### `PatAuthenticator` (external clients)

External MCP clients authenticate with static Personal Access Tokens configured in YAML. It deliberately declines any
`Bearer pmcp_…` token so it never collides with `McpAccessTokenAuthenticator`.

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
variables to keep secrets out of YAML files. The `PatAuthenticator` extracts the bearer token from the `Authorization`
header, looks up the username in the token map, loads the Pimcore `User`, validates it is active, and creates a
`SelfValidatingPassport`.

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

### `SessionBridgeAuthenticator` (legacy / session context)

Authenticates an MCP request against an existing Pimcore Studio session. It reads `_security_pimcore_admin` from the PHP
session (cross-context) via `AuthenticationResolverInterface::authenticateSession()`, validates that the user exists and is
active, and creates a `SelfValidatingPassport`. It returns `null` on failure so the next authenticator can try.

This was the original internal mechanism (agent-server forwarding the browser's `PHPSESSID`); it is retained for backward
compatibility but is no longer how the agent-server authenticates MCP calls - see the credential table above.

## PSR-7/PSR-17 Bridge Services

Studio Backend Bundle provides the PSR-7/PSR-17 bridge services required by MCP controllers globally. Bundles that implement
MCP servers do not need to register these services themselves:

- `Psr\Http\Message\ResponseFactoryInterface`
- `Psr\Http\Message\StreamFactoryInterface`
- `Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface`
- `Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface`

These are defined in `config/mcp.yaml` and available for autowiring in any bundle.

## Configuration Reference

```yaml
pimcore_studio_backend:
    mcp:
        authentication:
            tokens:
                # Map: Pimcore username => list of bearer tokens (external PATs)
                <username>:
                    - '<token-string-or-env-ref>'
```

The firewall is automatically configured by the bundle extension. To enable it, add the following to your
`config/packages/security.yaml` (see also [Installation](../02_Installation_and_Configuration/README.md)):

```yaml
security:
    firewalls:
        pimcore_mcp: '%pimcore_studio_backend.mcp_firewall_settings%'
    access_control:
        - { path: ^/pimcore-mcp/, roles: ROLE_PIMCORE_USER }
```

No manual firewall configuration beyond this is needed - the parameter contains the full firewall definition including the
authenticator chain, user provider, and stateless flag. Dynamic MCP access tokens (`Bearer pmcp_…`) need no configuration
here; they are issued at runtime by the consuming bundle.

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

Or use `SecurityServiceInterface::getCurrentUser()` which works with all authenticators.

### Operational notes

- **Transport security.** Dynamic MCP access tokens (`Bearer pmcp_…`) and static PATs are credentials. Serve Studio over
  HTTPS in production; over plain HTTP these tokens are sniffable on the wire.
- **Log redaction.** Tools that log raw request headers must redact `Authorization`. See `pimcore-agent-bundle` for the
  Fastify (agent-server) and Symfony (bundle) configuration.
- **Token lifecycle.** MCP access tokens expire after the consuming bundle's configured TTL (default 2h for
  `pimcore-agent-bundle`). Expired rows are removed by the `studio_mcp_access_token_gc` maintenance task. Tokens are revoked
  at the DB layer by an `ON DELETE CASCADE` FK on `users` - deleting a Pimcore user atomically clears their tokens.
