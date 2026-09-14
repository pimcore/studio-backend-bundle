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
  omitted value to `client_secret_basic`, and the server then issues a secret. There is no Client Credentials
  grant either way, so a client always acts for a logged-in user. Non-interactive machine access uses whatever
  static credential the target application supports, for example the
  [MCP token authenticator](../04_Development_Details/08_MCP_Server.md) (PAT).

## Enabling

The minimum configuration is the master switch, an issuer, signing keys, at least one protected resource,
and at least one way for a client to identify itself:

```yaml
# config/packages/pimcore_studio_backend.yaml
pimcore_studio_backend:
    oauth:
        enabled: true
        # Issuer identifier advertised in metadata, returned in the authorization
        # response, and stamped on every issued token. Required once enabled is true.
        issuer: 'https://pimcore.example.com'
        keys:
            private_key: '%env(OAUTH_PRIVATE_KEY)%'
            public_key: '%env(OAUTH_PUBLIC_KEY)%'
            passphrase: '%env(OAUTH_KEY_PASSPHRASE)%'
            encryption_key: '%env(OAUTH_ENCRYPTION_KEY)%'
        # Every token is issued for a named resource (RFC 8707), so at least one must
        # exist or the authorize endpoint refuses every request. This is the bundle's
        # own MCP endpoint; see "Protected resources (audiences)" below for more.
        resources:
            - uri: 'https://pimcore.example.com/pimcore-mcp'
              scopes_supported: ['mcp:read', 'mcp:write']
              authorization_servers: ['https://pimcore.example.com']
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

### What enabling it adds

Three things become operator-visible the moment the server is switched on:

- **Two database tables.** One records issued tokens so they can be revoked, one backs Dynamic Client
  Registration. They ship as bundle migrations, so run `bin/console doctrine:migrations:migrate` (or the
  Pimcore bundle installer) before the first authorization request.
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

For clients to actually *use* the token against an MCP server, two things are needed. The `pimcore_mcp`
firewall must be enabled, see the [MCP firewall setup](./README.md) (the *Optional: MCP firewall* step); its
authenticator chain includes an OAuth bearer authenticator that validates these tokens.

The MCP resource must also be **declared in configuration**. The bundle registers no protected resource of its
own, so without this entry there is nothing for a client to request a token for, and
`OAuthAccessTokenAuthenticator` has no registered resource matching the audience it enforces:

```yaml
pimcore_studio_backend:
    oauth:
        resources:
            # Must be the MCP base, with no trailing slash: the authenticator validates
            # every /pimcore-mcp/... request against this one URI, not against the
            # sub-path that was called.
            - uri: 'https://pimcore.example.com/pimcore-mcp'
              scopes_supported: ['mcp:read', 'mcp:write']
              authorization_servers: ['https://pimcore.example.com']
```

The authenticator derives that URI from the **request** host, so behind a reverse proxy the host Pimcore sees
must match this `uri` byte for byte, or valid tokens are rejected. See
[Deriving the resource URI](../04_Development_Details/07_OAuth_Protected_Applications.md#deriving-the-resource-uri).

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

## Protected resources (audiences)

Declare the endpoints that act as token audiences. Each becomes discoverable via Protected Resource Metadata.
Applications whose endpoints are only known at runtime register them programmatically instead, through
`ResourceRegistryInterface`, which is how Datahub Simple REST declares its own.

The authorization server issues nothing until at least one protected resource exists. Enabling it is therefore
not enough on its own: something has to declare a resource, and something has to accept tokens at it.

Every token is bound to one resource. The client names it with the RFC 8707 `resource` parameter, the
parameter is required, an unknown resource is rejected, and the resulting token is refused at any other
resource. See [Audience binding](../04_Development_Details/07_OAuth_Protected_Applications.md#audience-binding)
for the full rules.

`scopes_supported` caps what a token for that resource may carry: a client asking for more is narrowed to the
intersection, and one asking **only** for scopes the resource does not declare is refused with
`invalid_scope`. A resource declaring no scopes constrains nothing. Note the default is `['mcp:read']`, so set
it explicitly on any non-MCP resource.

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
| `issuer` | `null` | Issuer (`iss`) advertised in metadata, returned in the authorization response, stamped on tokens and verified by the resource server. **Required when `enabled` is `true`.** |
| `access_token_ttl` | `3600` | Access-token lifetime (seconds). |
| `auth_code_ttl` | `600` | Authorization-code lifetime (seconds). Also how long a pending authorization stays valid, i.e. how long the user has on the consent screen before it reports `oauth.consent.expired.*`. |
| `refresh_token_ttl` | `2592000` | Refresh-token lifetime (seconds). |
| `consent_path` | `/pimcore-studio/oauth/consent` | Pimcore Studio UI route the authorize endpoint redirects to for login/consent. Hard-coupled to the UI base URL: if you change `pimcore_studio_ui.url_path`, change this to match or the redirect lands on a `404`. |
| `allow_localhost_loopback_redirect` | `true` | Also accept `http://localhost:{port}` loopback redirect URIs. Set `false` for RFC 8252-strict (IP literals only). |
| `cors_allowed_origins` | `[]` | Browser origins allowed to call the OAuth endpoints cross-origin. Empty = any origin (wildcard); credentials are never sent. |
| `keys.private_key` | `null` | JWT signing private key (path or contents). |
| `keys.public_key` | `null` | JWT signing public key (path or contents). |
| `keys.passphrase` | `null` | Passphrase for the private key, if any. |
| `keys.encryption_key` | `null` | Encryption key for authorization codes and refresh tokens. |
| `clients` | `[]` | Pre-registered public clients, keyed by `client_id`; each has `name` + `redirect_uris` (see above). |
| `dynamic_client_registration.enabled` | `false` | Expose `POST /pimcore-oauth/register` and advertise it. |
| `client_id_metadata_documents.enabled` | `false` | Resolve URL-form `client_id`s and advertise support. |
| `client_id_metadata_documents.allowed_hosts` | `[]` | If non-empty, a `client_id` URL must be on one of these hosts. |
| `client_id_metadata_documents.allow_insecure` | `false` | Dev only: permit http/loopback `client_id` URLs. |
| `client_id_metadata_documents.cache_ttl` | `300` | Seconds to cache a fetched client metadata document. |
| `resources` | `[]` | Protected resources / token audiences. Per entry: `uri` (required), `scopes_supported` (defaults to `['mcp:read']`, which is rarely what a non-MCP resource wants, so set it explicitly) and `authorization_servers` (defaults to `[]`). |

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
