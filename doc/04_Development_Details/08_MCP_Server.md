---
title: MCP Server Infrastructure
description: Shared infrastructure for Model Context Protocol servers including firewall, authentication, and PSR-7 bridge.
---

# MCP Server Infrastructure (Experimental)

Pimcore Studio Backend provides shared infrastructure for [Model Context Protocol](https://modelcontextprotocol.io/)
(MCP) servers across bundles. This includes a dedicated security firewall, PSR-7/PSR-17 bridge services, and a
multi-authenticator security system supporting both internal agent use and external MCP clients.

## The `pimcore_mcp` Firewall

All MCP endpoints use the URL prefix `/pimcore-mcp/` and are protected by a dedicated Symfony firewall (`pimcore_mcp`).
This firewall is **stateless** - each request authenticates independently, with no session migration or security token
persistence. It is separate from the `pimcore_studio` firewall to provide security isolation - MCP authentication cannot
leak to Studio Backend API routes and vice versa.

All authenticators resolve to a Pimcore `User` object, and all existing Pimcore permissions (workspace ACLs, user/role
permissions) apply automatically. There are no MCP-specific scopes: if a user cannot edit a data object via Pimcore
Studio, they cannot edit it via MCP tools either.

### Who calls MCP endpoints, and with which credential

| Caller | Credential | Authenticator |
|--------|------------|---------------|
| Pimcore AI agent-server (internal, per chat session) | `Authorization: Bearer pmcp_…` | `McpAccessTokenAuthenticator` |
| External MCP client (Claude Desktop, Cursor, …) | `Authorization: Bearer <static PAT>` | `PatAuthenticator` |
| Caller presenting a Pimcore Studio session cookie | Pimcore session cookie (`PHPSESSID`) | `SessionBridgeAuthenticator` |

The **primary internal path is the MCP access token** (`Bearer pmcp_…`): the Pimcore AI agent-server mints one dynamic,
expiring, revocable token per chat session (see [Minting MCP access tokens](#minting-mcp-access-tokens)) and sends it on
every `/pimcore-mcp/` request. The bearer binds each request to a specific chat session and stays valid across
browser-session expiry, which matters for long-running agent runs. `SessionBridgeAuthenticator` handles requests that
instead carry a Pimcore Studio session cookie.

### Authenticator chain

The firewall tries these authenticators in order. The first two return `null` on failure so the next one can try;
`PatAuthenticator` is last and therefore owns the terminal response, answering `401` (or `429` when throttled):

| Order | Authenticator | Trigger | Use case |
|-------|---------------|---------|----------|
| 1 | `SessionBridgeAuthenticator` | Pimcore session cookie present | Requests that carry a Pimcore Studio session cookie |
| 2 | `McpAccessTokenAuthenticator` | `Authorization: Bearer pmcp_…` | Internal: dynamically-issued, expiring, revocable per-chat-session tokens (Pimcore AI agent) |
| 3 | `PatAuthenticator` | `Authorization: Bearer <other>` | External: MCP clients (Claude Desktop, Cursor, etc.) |

### `McpAccessTokenAuthenticator` (primary internal)

Validates a `pmcp_`-prefixed bearer token via `McpAccessTokenService` (DB-backed, hashed at rest, TTL-bounded,
revocable). It **never reads the PHP session**. On success, the validated token's `reference` (the chat session id) is
stashed on the request attributes (`_mcp_token_reference`) so trusted downstream code can use it instead of any
forge-able header. On failure it returns `null`, so the firewall falls through to `PatAuthenticator`.

The studio-backend bundle owns both validation (`McpAccessTokenAuthenticator`) and the issuance/refresh/revoke
primitives. Consuming bundles (e.g. `pimcore-agent-bundle`) call those primitives to mint tokens for their own MCP
servers - see [Minting MCP access tokens](#minting-mcp-access-tokens).

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

Each key is a Pimcore username, and the value is a list of accepted tokens for that user. Tokens can reference
environment variables to keep secrets out of YAML files. The `PatAuthenticator` extracts the bearer token from the
`Authorization` header, looks up the username in the token map, loads the Pimcore `User`, validates it is active, and
creates a `SelfValidatingPassport`.

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

### `SessionBridgeAuthenticator` (session cookie)

Authenticates an MCP request against an existing Pimcore Studio session. It reads `_security_pimcore_admin` from the PHP
session (cross-context) via `AuthenticationResolverInterface::authenticateSession()`, validates that the user exists and
is active, and creates a `SelfValidatingPassport`. It returns `null` on failure so the next authenticator can try.

It applies to requests that arrive with a Studio session cookie. The agent-server's own MCP calls use the `pmcp_…`
bearer instead (see the credential table above).

## Minting MCP access tokens

`McpAccessTokenService` (behind `McpAccessTokenServiceInterface`) is the API a bundle uses to mint and manage dynamic
MCP access tokens for its own MCP servers. Inject the interface and call:

| Method | Purpose |
|--------|---------|
| `issue(int $userId, int $ttlSeconds, string $reference): string` | Mint a token for a user, bound to `reference`, valid for `ttlSeconds`. Returns the raw `pmcp_…` token - the only time it is available in clear text. |
| `refresh(string $reference, int $ttlSeconds): bool` | Extend the live token for `reference` by a fresh `ttlSeconds` window. Returns `false` if no live token exists or the user is no longer valid. |
| `revoke(string $reference): void` | Delete the token for `reference`. |
| `revokeByUser(int $userId): void` | Delete all tokens for a user. |
| `validate(string $token): ?ValidatedAccessToken` | Resolve a raw token to its `{ user, reference }` (used by `McpAccessTokenAuthenticator`). |

### Semantics

- **`reference` is your correlation key.** An opaque string the bundle chooses - the Pimcore AI agent-server uses the
  chat session id. It is what `refresh()` / `revoke()` operate on, and it is exposed to authenticated tool code via the
  `_mcp_token_reference` request attribute.
- **One live token per `reference`.** `issue()` deletes any existing token for the same `reference` before creating the
  new one, so re-issuing rotates the token rather than accumulating rows.
- **TTL is a sliding window.** `refresh()` moves the expiry to `now + ttlSeconds`; a caller keeps a long-running session
  alive by refreshing on a timer.
- **The raw token is returned once.** Only its SHA-256 hash is stored, so a lost token cannot be recovered - only
  re-issued.
- **Validation re-checks the user.** `validate()` rejects a token whose user is no longer valid, so deactivating or
  deleting a user immediately stops their tokens working, independent of expiry.

### Token format and storage

| Aspect | Value |
|--------|-------|
| Prefix | `pmcp_` (`McpAccessTokenService::TOKEN_PREFIX`) |
| Entropy | 32 random bytes, hex-encoded |
| At rest | SHA-256 hash only, in table `bundle_studio_mcp_access_token` (`token_hash`, `user_id`, `reference`, `expires_at`, `created_at`) |
| Expiry | `expires_at` (unix seconds); expired rows are pruned by the `studio_mcp_access_token_gc` maintenance task |

For a worked example of a mint / refresh / re-mint / revoke policy on top of these primitives - including *when* to mint
vs. extend and how a client paces refreshes - see the Pimcore Agent Bundle's *MCP Integration → Token lifecycle*
documentation.

## PSR-7/PSR-17 Bridge Services

Studio Backend Bundle provides the PSR-7/PSR-17 bridge services required by MCP controllers globally. Bundles that
implement MCP servers do not need to register these services themselves:

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

The firewall is automatically configured by the bundle extension. To enable it, add this to your
`config/packages/security.yaml` (see also [Installation](../02_Installation_and_Configuration/README.md)):

```yaml
security:
    firewalls:
        pimcore_mcp: '%pimcore_studio_backend.mcp_firewall_settings%'
    access_control:
        - { path: ^/pimcore-mcp/, roles: ROLE_PIMCORE_USER }
```

No manual firewall configuration beyond this is needed - the parameter contains the full firewall definition including
the authenticator chain, user provider, and stateless flag. Dynamic MCP access tokens (`Bearer pmcp_…`) need no
configuration here; they are issued at runtime by the consuming bundle.

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

The `pimcore_mcp` firewall automatically handles authentication for any route matching `^/pimcore-mcp/`. No custom auth
code is needed in your bundle.

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
  `pimcore-agent-bundle`); expired rows are pruned by the `studio_mcp_access_token_gc` maintenance task. A token also
  stops working the moment its user is deactivated or removed - `validate()` re-checks the user on every request - and a
  bundle can drop a user's tokens explicitly with `revokeByUser()`. See [Minting MCP access
  tokens](#minting-mcp-access-tokens).

### Throttling guessed credentials

Guesses at a **static PAT** are throttled per client IP: **5 failures per 5 minutes**. A throttled client receives
`429 Too Many Requests` with a `Retry-After` header in seconds; an ordinary authentication failure stays `401`.

A credential that resolves to a user is never throttled. That is a property of how the buckets are keyed, not a
threshold to tune. `PatAuthenticator` puts the resolved username on the passport when a bearer token matches the
configured token map, and a shared placeholder identifier when it matches nothing. `McpLoginRateLimiter` hands out a
bucket for the placeholder only, so a recognised credential is exempt from both the block and the count - there is no
bucket it could be rejected from.

The guarantee is per credential, not per request. `AuthenticatorManager` keeps running authenticators after one has
already succeeded, so a request that presents an unrecognised PAT *alongside* a valid Studio session cookie is still
judged on the PAT and can be answered with the throttled response. Such a request already failed before throttling
existed - `PatAuthenticator` owns the terminal response and answered it with `401` - so what changes is the status
code, not whether the request works. Send one credential per request.

`McpLoginRateLimiter` is supplied as `login_throttling.limiter`, which makes Symfony skip the `DefaultLoginRateLimiter`
it would otherwise build for the firewall. That default derives a second limiter keyed on IP alone, and that tier is
charged by every client on an address and peeked by every client on it - so guesses aimed at one credential can push an
unrelated valid credential into a `429`.

Two credential types are deliberately **not** throttled:

- **Dynamic tokens** (`pmcp_…`) are 32 random bytes. Guessing one is not a reachable attack, so a counter adds nothing,
  while a shared bucket for them would let guesses interfere with a live chat session. Their protection is entropy,
  short TTLs, SHA-256 storage and `revokeByUser()` - see [Minting MCP access tokens](#minting-mcp-access-tokens).
- **Session bridge** requests authenticate against an existing Pimcore Studio session, which has its own protections.

The budget is not reset by a successful authentication: Symfony skips that reset for peekable limiters, so failures
decay when the fixed window rolls over. Retrying while throttled does not extend the lockout, because the fixed-window
limiter declines an over-limit request without recording it - but honour `Retry-After` rather than polling. Because the
threshold is reported in whole minutes, `Retry-After` rounds up to the next minute.

The client IP comes from `Request::getClientIp()`. Configure `framework.trusted_proxies` / `framework.trusted_headers`
if the application sits behind a reverse proxy or load balancer; otherwise every client behind the proxy shares one
bucket keyed on the proxy's own address.

To change the threshold, redefine the limiter in the application:

```yaml
# config/packages/framework.yaml
framework:
    rate_limiter:
        studio_mcp_login:
            policy: 'fixed_window'
            limit: 10
            interval: '5 minutes'
```

To replace the mechanism entirely, define the whole `pimcore_studio_backend.mcp_firewall_settings` parameter. The
bundle only sets it when it is not already defined, so an application-supplied value wins:

```yaml
# config/packages/security.yaml (or any file loaded before the bundle's extension runs)
parameters:
    pimcore_studio_backend.mcp_firewall_settings:
        pattern: '^/pimcore-mcp/'
        user_checker: Pimcore\Security\User\UserChecker
        provider: pimcore_studio_backend
        stateless: true
        login_throttling:
            limiter: Pimcore\Bundle\StudioBackendBundle\Security\RateLimiter\McpLoginRateLimiterInterface
        custom_authenticators:
            - Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\SessionBridgeAuthenticator
            - Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\McpAccessTokenAuthenticator
            - Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\PatAuthenticator
```

`login_throttling.max_attempts` and `login_throttling.interval` have no effect while `limiter` is set - Symfony only
reads them when it has to build a limiter itself.

The firewall is declared as a whole-value substitution -
`pimcore_mcp: '%pimcore_studio_backend.mcp_firewall_settings%'` - so it cannot be partially overridden under
`security.firewalls.pimcore_mcp`. Writing only `login_throttling` there **replaces** the entire firewall definition and
drops the pattern, provider and authenticators with it. Copy the block above and change what you need.

### Request rate limiting

MCP endpoints are covered by their own limiter, `studio_mcp_general`: **3000 requests per minute per client IP**,
reported through `X-RateLimit-Limit`, `X-RateLimit-Remaining` and `X-RateLimit-Reset` on every response. Exceeding it
returns `429` with the standard Studio error body.

It is deliberately separate from `studio_api_general`, which covers the Studio API and is sized for a browser UI. MCP
carries machine traffic, and a single agent server can serve every chat in an installation from one address, so the
ceiling here guards against denial of service rather than metering individual users. Retune it the same way as above:

```yaml
framework:
    rate_limiter:
        studio_mcp_general:
            policy: 'sliding_window'
            limit: 10000
            interval: '1 minute'
```
