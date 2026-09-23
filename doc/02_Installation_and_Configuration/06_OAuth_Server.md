---
title: OAuth 2.1 Authorization Server
description: Embedded, opt-in OAuth 2.1 authorization server for authenticating MCP and other API clients.
---

# OAuth 2.1 Authorization Server (Experimental)

The Studio Backend Bundle ships an embedded **OAuth 2.1 authorization server**. It lets standards-based
clients obtain a bearer token and call Pimcore endpoints on behalf of a Pimcore user, without static
credentials.

This page covers running the authorization server: enabling it, key material, endpoints, and onboarding
clients. It issues tokens and does not care which endpoints they are presented to. Accepting those tokens is
a separate role, filled by any bundle that makes its endpoints a *resource server*. To build one, see
[OAuth-Protected Applications](../04_Development_Details/07_OAuth_Protected_Applications.md).

It is **opt-in** (off by default) and deliberately **isolated from your application's global security
configuration**: enabling it adds a self-contained set of routes and does not change how the rest of your
firewalls behave.

> **Experimental.** The feature is under active development; configuration keys and behavior may change
> between minor versions. Enable it consciously and pin the bundle version.

Two applications accept its tokens: the bundle's own [MCP firewall](../04_Development_Details/08_MCP_Server.md),
where OAuth is one of several accepted credentials, and Datahub Simple REST. Neither is privileged; both build
on the same public contracts, and any bundle can do the same.

## What it provides

- **Discovery** - Authorization Server Metadata ([RFC 8414](https://www.rfc-editor.org/rfc/rfc8414)) and
  Protected Resource Metadata ([RFC 9728](https://www.rfc-editor.org/rfc/rfc9728)).
- **Authorization Code grant with PKCE** ([RFC 7636](https://www.rfc-editor.org/rfc/rfc7636)) - required from
  every client, public or confidential, using the `S256` method; a missing challenge and `plain` are both
  rejected.
- **Refresh tokens**.
- Three ways to onboard clients: **pre-registered** clients declared in config, optional **Dynamic Client
  Registration** ([RFC 7591](https://www.rfc-editor.org/rfc/rfc7591)), and optional **Client ID Metadata
  Documents**. Pre-registered and metadata-document clients are always public (PKCE, no secret). A dynamically
  registered client is public only when it registers `token_endpoint_auth_method: none`; RFC 7591 defaults an
  omitted value to `client_secret_basic`, and the server then issues a secret. Those two are the only accepted
  values: `client_secret_post` is refused at registration and not advertised, because the transport a client
  used is no longer distinguishable by the time the request reaches this server, so registering it would
  record a preference nothing could honour. There is no Client Credentials
  grant either way, so a client always acts for a logged-in user. Non-interactive machine access uses whatever
  static credential the target application supports, for example the
  [MCP token authenticator](../04_Development_Details/08_MCP_Server.md) (PAT).

## Enabling

The minimum configuration is the master switch, an issuer, signing keys, and at least one way for a client to
identify itself:

```yaml
# config/packages/pimcore_studio_backend.yaml
pimcore_studio_backend:
    oauth:
        enabled: true
        # Issuer identifier advertised in metadata, returned in the authorization
        # response, and stamped on every issued token. Required once enabled is true.
        # A literal such as 'https://pimcore.example.com' works as well.
        issuer: '%env(OAUTH_ISSUER)%'
        keys:
            private_key: '%env(OAUTH_PRIVATE_KEY)%'
            public_key: '%env(OAUTH_PUBLIC_KEY)%'
            encryption_key: '%env(OAUTH_ENCRYPTION_KEY)%'
            # Only when the private key has one. Keys generated as shown below do not,
            # and naming an env var that is never defined fails the build.
            #passphrase: '%env(OAUTH_KEY_PASSPHRASE)%'
        # A client has to be resolvable before a resource is ever consulted. With no
        # pre-registered client and both self-registration mechanisms off, every
        # authorization request fails with `invalid_client`. See "Onboarding clients".
        clients:
            my-desktop-app:
                name: 'My Desktop App'
                redirect_uris:
                    - 'http://127.0.0.1:33418/callback'
```

> Reference key material via environment variables or Symfony secrets. **Never commit keys.**

> `issuer`, `keys.private_key`, `keys.public_key` and `keys.encryption_key` are validated at container build
> once `enabled` is `true`: leaving any of them unset fails the build with a message naming the key, rather
> than starting a server that cannot issue a token. `passphrase` is genuinely optional.

> The issuer is an origin without a path, and the authorization server's endpoints and every protected
> resource URI are built on it. Pimcore therefore has to be served from the root of that origin: an installation
> under a path prefix such as `/cms` advertises URIs that do not match its routes, and is not supported.

> A literal issuer is checked for this shape at container build. A value from an environment variable is only
> known at runtime, so it is checked on the first request to an OAuth endpoint instead: while it is malformed,
> every OAuth endpoint answers `500 server_error` and the reason is logged. The issuer has to come from one
> variable as a whole (`'%env(OAUTH_ISSUER)%'`); a value assembled around one, such as
> `'https://%env(OAUTH_HOST)%'`, fails the build.

> Every token is issued for a named resource (RFC 8707), so at least one has to exist. The bundle contributes
> its own MCP endpoints, so this configuration is enough to get a working server; add entries under
> `resources` only for further endpoints. See [Protected resources (audiences)](#protected-resources-audiences).

### What enabling it adds

The corollary first: while `enabled` is `false` every OAuth path answers `404`, not `403` and not a routing
error. That covers `/pimcore-oauth/*`, `/.well-known/oauth-*` and `<url_prefix>/oauth/*`, so a server that
looks absent is usually a toggle that never took effect.

Three things become operator-visible the moment the server is switched on:

- **Two database tables.** One records issued tokens so they can be revoked, one backs Dynamic Client
  Registration. A fresh install of the bundle creates them; an installation that already exists gets them
  from `bin/console doctrine:migrations:migrate`. Those cover different situations rather than being
  alternatives, so on an existing installation run the migrations before the first authorization request.
  Without the tables, enabling OAuth fails on that first request with a `TableNotFoundException`.
  The token table also holds the resource each token was issued for, so it is not a cache: emptying it, or
  restoring the database from a backup taken before a token was issued, invalidates the authorization codes
  and refresh tokens issued until then. Those are refused with `invalid_grant`, and the client has to run a
  new authorization.
- **A maintenance task.** `OAuthTokenGcTask` prunes expired token records and runs as part of
  `bin/console pimcore:maintenance`. Without that cron the table grows without bound.
- **Two filesystem cache pools.** `pimcore_studio_backend.oauth.pending_authorization` and
  `...oauth.client_metadata` are prepended into `framework.cache.pools`. The first deliberately uses the
  filesystem adapter rather than your `cache.app`: the authorize request and the later consent approval can
  land on different workers, and a per-process adapter such as APCu would lose the pending request between
  them.

### Generating keys

The signing keys are an RSA key pair; the encryption key is a random string used for authorization codes and
refresh tokens:

```bash
# RSA signing key pair (add -passout pass:... if you set a passphrase)
openssl genrsa -out oauth-private.key 2048
openssl rsa -in oauth-private.key -pubout -out oauth-public.key

# Encryption key (e.g. 32 random bytes, base64-encoded)
php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'
```

`private_key`/`public_key` accept either a file path or the key contents.

## Exposing the endpoints

The OAuth routes live at the **web root**, outside the `%pimcore_studio_backend.url_prefix%` (Pimcore Studio
API) and outside the `pimcore_mcp` firewall. All of them must be **publicly reachable**, the authorize endpoint
included: it performs no user lookup of its own. It validates the request, stashes it under an opaque id and
redirects to `oauth.consent_path`; the login and the consent screen happen there, on a Pimcore Studio UI route
that enforces the session.

No firewall or `access_control` rule ships for these paths, and Symfony imposes nothing where no rule
matches, so on a default installation they are already reachable. You only need to whitelist them if **your**
project restricts by default, for example with a catch-all `- { path: ^/, roles: ROLE_USER }`:

```yaml
security:
    access_control:
        # Only needed if a broader rule of yours would otherwise catch these
        - { path: '^/\.well-known/oauth-', roles: PUBLIC_ACCESS }
        - { path: '^/pimcore-oauth/(authorize|token|register)$', roles: PUBLIC_ACCESS }
```

Include `authorize` in that list as well as `token` and `register`. It is the one people leave out, and a
catch-all rule catching it kills the flow at its first redirect rather than at the consent screen.

The consent page itself (`oauth.consent_path`, `/pimcore-studio/oauth/consent` by default) is also public: it
sits outside the `pimcore_studio` firewall, whose pattern is `^/pimcore-studio/api(/.*)?$`. That is
deliberate. The page is a Pimcore Studio UI route, and the authentication happens in the Studio API call it
makes to fetch the pending authorization, not in serving the page.

### Accepting tokens at the MCP endpoints

For clients to actually *use* the token against an MCP server, the `pimcore_mcp` firewall must be enabled, see
the [MCP firewall setup](./README.md) (the *Optional: MCP firewall* step); its authenticator chain includes an
OAuth bearer authenticator that validates these tokens. The MCP resource itself needs no configuration, and is
described in [MCP Server Infrastructure](../04_Development_Details/08_MCP_Server.md#oauth-protected-resource).

## Endpoints

| Path | Method | Purpose |
|------|--------|---------|
| `/.well-known/oauth-authorization-server` | GET | Authorization Server Metadata (RFC 8414) - public discovery |
| `/.well-known/oauth-protected-resource{/path}` | GET | Protected Resource Metadata (RFC 9728) - advertises the audience + auth server for a resource |
| `/pimcore-oauth/authorize` | GET | Browser entry point; redirects to the Pimcore Studio consent UI (`oauth.consent_path`) |
| `/pimcore-oauth/token` | POST | Token endpoint (authorization_code, refresh_token) |
| `/pimcore-oauth/register` | POST | Dynamic Client Registration (RFC 7591) - returns `404` unless enabled |

The consent screen is driven by two further endpoints. Unlike the ones above they sit **under the Pimcore
Studio API prefix** and need an authenticated Pimcore Studio session, because they act for the logged-in user:

| Path | Method | Purpose |
|------|--------|---------|
| `%url_prefix%/oauth/authorizations/{id}` | GET | Details of a pending authorization: client, user and the scopes being asked for |
| `%url_prefix%/oauth/authorizations/{id}` | POST | Approve or deny it; returns the location to send the browser to |

`%url_prefix%` is `pimcore_studio_backend.url_prefix` (`/pimcore-studio/api` by default). They are consumed by
the Pimcore Studio consent screen rather than by OAuth clients directly.

## How a client authenticates

The Authorization Code + PKCE flow, end to end:

1. The client reads `/.well-known/oauth-authorization-server` to discover the endpoints.
2. It sends the user to `/pimcore-oauth/authorize` with a PKCE `code_challenge` (`S256`). The endpoint
   redirects to the Pimcore Studio consent UI (`oauth.consent_path`), where the user logs in and approves.
3. On approval the client receives an authorization code and exchanges it at `/pimcore-oauth/token`, presenting
   the PKCE `code_verifier`. It gets an access token (a signed JWT) and, optionally, a refresh token.
4. The client calls the resource it named, presenting `Authorization: Bearer <jwt>`. For an endpoint behind
   the `pimcore_mcp` firewall, its `OAuthAccessTokenAuthenticator` validates the token and resolves the
   Pimcore user; another application validates it wherever it already authenticates.

A `401` from a protected resource carries a `WWW-Authenticate` challenge pointing at the resource's metadata, so
a compliant client can discover where to authenticate.

> **Scopes are labels, not permissions.** A token carries only the scopes the resource it names declares, but
> nothing compares a granted scope against an operation: each application applies its own authorization rules.
> See [What the platform leaves to each application][scope-enforcement].

[scope-enforcement]: ../04_Development_Details/07_OAuth_Protected_Applications.md#what-the-platform-leaves-to-each-application

## Onboarding clients

Use one or several of the following. All three authenticate a logged-in Pimcore user via the Authorization
Code flow.

### Pre-registered clients

Declare known clients directly in config - first-party clients you control, or any client that supports
neither of the self-registration mechanisms below. Each entry is a `client_id` (the map key) with an
allow-list of redirect URIs:

```yaml
pimcore_studio_backend:
    oauth:
        clients:
            my-desktop-app:
                name: 'My Desktop App'
                redirect_uris:
                    - 'http://127.0.0.1:33418/callback'
                    - 'http://localhost:33418/callback'
```

Pre-registered clients are **public only** - there is no `secret`, `confidential`, or `service_user` field,
and no Client Credentials grant. They resolve **before** Client ID Metadata Documents and Dynamic Client
Registration, and work even when both of those are disabled - so they are the onboarding path for a
locked-down deployment that exposes no open registration endpoint.

### Dynamic Client Registration (RFC 7591)

Lets clients without prior credentials self-register at an **open, unauthenticated** endpoint. Opt-in:

```yaml
pimcore_studio_backend:
    oauth:
        dynamic_client_registration:
            enabled: true
```

Enable it deliberately - the `/pimcore-oauth/register` endpoint becomes publicly writable and is advertised in
metadata.

**The `grant_types` a client registers are enforced.** A client that registered
`grant_types: ["authorization_code"]` is refused with `unauthorized_client` if it presents itself at the token
endpoint for `refresh_token`, and is issued no refresh token by the authorization-code flow either. Register
`["authorization_code", "refresh_token"]` for a client that needs to refresh. Omitting `grant_types` gives
`["authorization_code"]`, so a client that wants refresh tokens has to say so.

Clients declared in `oauth.clients` are unrestricted: the configuration has no `grant_types` key, so there is
no restriction to read and none is inferred.

Clients identified by a Client ID Metadata Document are also unrestricted, but for a different reason. A CIMD
document **can** declare `grant_types`, since the draft draws its fields from the same registry dynamic
registration uses, but this bundle reads only `client_id`, `redirect_uris` and `client_name` from it. A
document that declares its grants is therefore not restricted by them. Nothing is granted that the document
did not ask for, but do not rely on a CIMD `grant_types` to narrow a client.

Two further controls bound what that endpoint can be made to do, and they cover different callers.

**Registration is idempotent for public clients.** A repeat registration whose metadata matches a client that
already exists returns that same `client_id` instead of creating another record. The comparison is over the
metadata the client chose: `redirect_uris` (order-insensitive and de-duplicated), `client_name` (trimmed),
`grant_types`, `scope` (de-duplicated) and `token_endpoint_auth_method`. So a client that re-registers every
time it starts, which several MCP clients do, accumulates one row rather than one per start. This is always
on, and is not affected by the rate-limiting setting below.

It is **not** a defence against a hostile caller. Every input to that comparison comes from the request, so
anyone willing to vary a client name by one character gets a new row every time. What it removes is the
accidental growth from well-behaved clients; the rate limit is what bounds the deliberate kind.

Confidential clients are **never** deduplicated and always get a new record. Their secret is shown once and
kept only as a hash, so a repeat call could not return the original, and reissuing one would silently
invalidate the secret an already-deployed copy of that client is using. Public clients, which use PKCE and
hold no secret, are the normal case for MCP.

**The register endpoint is rate limited** to 60 requests per hour per client IP (`fixed_window`). That is a
denial-of-service ceiling rather than a quota: with idempotency doing the real work, legitimate traffic stays
far below it. It respects `pimcore_studio_backend.rate_limiting.enabled` like the bundle's other limiters.
Override it by redefining the limiter, since `rate_limiter.yaml` is prepended rather than final:

```yaml
framework:
    rate_limiter:
        studio_oauth_register:
            policy: 'fixed_window'
            limit: 300
            interval: '1 hour'
```

The key is the client IP as Symfony sees it, which behind a reverse proxy is the **proxy's** address unless
[`trusted_proxies`](https://symfony.com/doc/current/deployment/proxies.html) is configured. Without it every
registration shares one bucket.

`/pimcore-oauth/token` and `/pimcore-oauth/authorize` are deliberately **not** rate limited. See
[Hosted AI connectors](#hosted-ai-connectors).

### Client ID Metadata Documents

Instead of registering, a client presents an **HTTPS URL as its `client_id`**; the server fetches the client
metadata from that URL. Opt-in, with host allow-listing:

```yaml
pimcore_studio_backend:
    oauth:
        client_id_metadata_documents:
            enabled: true
            allowed_hosts: ['client.example.com']
            allow_insecure: false   # dev only; permits http/loopback URLs
            cache_ttl: 300
```

### Hosted AI connectors

Local clients (Pimcore Studio's own agent, Claude Desktop, Claude Code, Cursor) run on the user's machine and
redirect to loopback. Hosted connectors (claude.ai on the web, ChatGPT connectors) do not: the MCP client is
the *provider's* infrastructure, so registration and token traffic reach this installation from that
provider's egress range, shared by every one of its customers worldwide.

Two consequences.

**Prefer Client ID Metadata Documents over Dynamic Client Registration** if you expect many such connections.
A CIMD client is identified by a URL the provider already publishes, so **no registration happens at all**:
nothing is written, there is nothing to deduplicate, and the register endpoint can stay off entirely.

**Per-IP limits describe a provider, not a tenant.** This is why only the register endpoint carries one, and
why `/pimcore-oauth/token` and `/pimcore-oauth/authorize` carry none. Every user's token exchange and refresh
arrives from the same handful of provider addresses, so an IP bucket on those endpoints would let one busy
organisation throttle unrelated ones against this installation. Limiting them usefully needs a per-client or
per-user key, not a per-IP one. Registration is different: it is unauthenticated and it writes, so a ceiling
there is worth its cost.

## Protected resources (audiences)

Declare the endpoints that act as token audiences. Each becomes discoverable via Protected Resource Metadata.
Bundles contribute their own by implementing `ProtectedResourceProviderInterface`, which is how Datahub Simple
REST declares its endpoints and how this bundle declares its
[MCP endpoints](../04_Development_Details/08_MCP_Server.md#oauth-protected-resource) - so expect entries here
you did not configure. Declaring the same URI yourself overrides the contributed one.

The authorization server issues nothing until at least one protected resource exists. Enabling it is enough to
get one: this bundle contributes its MCP endpoints, so a server with no `resources` entries at all still
issues tokens for them. Something still has to *accept* those tokens at the other end, which for the MCP
endpoints means enabling the `pimcore_mcp` firewall.

Every token is bound to one resource. The client names it with the RFC 8707 `resource` parameter, the
parameter is required, an unknown resource is rejected, and the resulting token is refused at any other
resource. See [Audience binding](../04_Development_Details/07_OAuth_Protected_Applications.md#audience-binding)
for the full rules.

`scopes_supported` does two jobs. It caps what a token for that resource may carry, so a client asking for
more is narrowed to the intersection and one asking **only** for scopes the resource does not declare is
refused with `invalid_scope`. And it is **how a scope comes to exist at all**: the server's catalogue, which
the authorization endpoint accepts, dynamic clients may register and the metadata advertises, is the union of
the `scopes_supported` of every registered resource. There is nothing else to declare, and nothing that can
disagree with it.

A resource declaring no scopes constrains nothing and contributes nothing, which is the default (`[]`).

```yaml
pimcore_studio_backend:
    oauth:
        resources:
            - uri: 'https://pimcore.example.com/my-bundle/api'
              scopes_supported: ['mybundle:read']
              authorization_servers: ['https://pimcore.example.com']
```

## Configuration reference

All keys live under `pimcore_studio_backend.oauth`.

| Key | Default | Purpose |
|-----|---------|---------|
| `enabled` | `false` | Master switch for the embedded authorization server. |
| `issuer` | `null` | Issuer (`iss`) advertised in metadata, returned in the authorization response, stamped on tokens and verified by the resource server. An origin only (scheme, host, optional port), without a path. **Required when `enabled` is `true`.** |
| `access_token_ttl` | `3600` | Access-token lifetime (seconds). |
| `auth_code_ttl` | `600` | Authorization-code lifetime (seconds). Also how long a pending authorization stays valid, i.e. how long the user has on the consent screen before it reports `oauth.consent.expired.*`. |
| `refresh_token_ttl` | `2592000` | Refresh-token lifetime (seconds). |
| `consent_path` | `/pimcore-studio/oauth/consent` | Pimcore Studio UI route the authorize endpoint redirects to for login/consent. Hard-coupled to the UI base URL: if you change `pimcore_studio_ui.url_path`, change this to match or the redirect lands on a `404`. |
| `allow_localhost_loopback_redirect` | `true` | Also accept `http://localhost:{port}` loopback redirect URIs. Set `false` for RFC 8252-strict (IP literals only). |
| `cors_allowed_origins` | `[]` | Browser origins allowed to call the OAuth endpoints cross-origin. Empty = any origin (wildcard); credentials are never sent. |
| `keys.private_key` | `null` | JWT signing private key (path or contents). **Required when `enabled` is `true`**, validated at container build. |
| `keys.public_key` | `null` | JWT signing public key (path or contents). **Required when `enabled` is `true`**, validated at container build. |
| `keys.passphrase` | `null` | Passphrase for the private key, if any. Optional; a key without one is normal. |
| `keys.encryption_key` | `null` | Encryption key for authorization codes and refresh tokens. **Required when `enabled` is `true`**, validated at container build. |
| `clients` | `[]` | Pre-registered public clients, keyed by `client_id`; each has `name` + `redirect_uris` (see above). |
| `dynamic_client_registration.enabled` | `false` | Expose `POST /pimcore-oauth/register` and advertise it. Has no effect while `enabled` is `false`: the endpoint answers `404` either way. |
| `client_id_metadata_documents.enabled` | `false` | Resolve URL-form `client_id`s and advertise support. |
| `client_id_metadata_documents.allowed_hosts` | `[]` | If non-empty, a `client_id` URL must be on one of these hosts. |
| `client_id_metadata_documents.allow_insecure` | `false` | Dev only: permit http/loopback `client_id` URLs. |
| `client_id_metadata_documents.cache_ttl` | `300` | Seconds to cache a fetched client metadata document. |
| `resources` | `[]` | Additional protected resources / token audiences; the bundle's own `/pimcore-mcp` is registered without configuration. Per entry: `uri` (required), `scopes_supported` (default `[]`, and also what defines the scope catalogue) and `authorization_servers` (default `[]`). An entry whose `uri` matches a built-in one replaces it. |

## Security considerations

- **Prefer pre-registered clients when you know your clients up front.** They need no publicly writable
  registration endpoint; keep Dynamic Client Registration off unless anonymous clients must self-register.
- **Dynamic Client Registration is open registration.** Enable it only when you intend anonymous clients to
  self-register, and consider the network exposure of `/pimcore-oauth/register`.
- **`allow_insecure` and loopback allowances are development conveniences.** Never enable `allow_insecure` in
  production; disable `allow_localhost_loopback_redirect` if your clients use IP-literal loopback redirects.

## Related

- [MCP Server Infrastructure](../04_Development_Details/08_MCP_Server.md) - the `pimcore_mcp` firewall,
  authenticator chain, and static-token authentication.
- [Installation and Configuration](./README.md) - bundle install and firewall setup.
