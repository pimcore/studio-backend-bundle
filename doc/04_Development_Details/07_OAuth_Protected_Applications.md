---
title: OAuth-Protected Applications
description: Build a bundle's endpoints into an OAuth resource server using the embedded authorization server.
---

# OAuth-Protected Applications (Experimental)

The [embedded OAuth 2.1 authorization server](../02_Installation_and_Configuration/06_OAuth_Server.md) is a
platform capability, not a feature of any one bundle. It issues tokens; it does not care what those tokens
are eventually presented to.

Any bundle can accept those tokens and become an **OAuth-protected application**. This page describes the
contracts to build against and the shape such an application takes, then walks through adding one.

> **Experimental.** Contracts on this page are public API, but the surrounding feature is under active
> development. Pin the bundle version.

## Two roles

OAuth splits into two roles, and this bundle fills only the first by default:

| Role | Responsibility | Who |
|------|----------------|-----|
| Authorization server | Authenticates the human, runs consent, issues and revokes tokens | This bundle, once enabled. Exactly one per installation. |
| Resource server | Accepts a token on its own endpoints, resolves it to a Pimcore user, decides what that user may do | Any bundle. Several per installation. |

A resource server never issues, refreshes or revokes tokens, and never needs the signing keys. It validates
what it is handed and applies its own authorization rules.

## Applications today

| Application | Endpoint | Authenticates in | Authorization model |
|-------------|----------|------------------|---------------------|
| Pimcore MCP servers | `/pimcore-mcp/…` | a Symfony firewall | The resolved user's own Pimcore permissions, plus per-server sharing |
| Data Hub Simple REST | `/pimcore-datahub-webservices/simplerest…` (REST and MCP) | a request-argument resolver and a controller base class | Per-configuration allow-list of users and roles; data exposure stays driven by the Data Hub configuration |

They differ deliberately, and in more than one dimension. Authentication is shared; **authorization is each
application's own business**, and so is *where* the credential is checked. The platform tells you *who* is
calling, never *what they may do*.

Data Hub Simple REST is worth studying as an example of one resource covering two surfaces: its REST
endpoints and its MCP endpoint share a single protected resource, so a user consents once and the
resulting token works across both. Each surface checks the token where it already authenticated,
which is why the same application appears twice in the "authenticates in" column.

## Public contracts

| Contract | Purpose |
|----------|---------|
| `OAuth\Contract\ScopeProviderInterface` | Contribute your own scope identifiers to the server's catalogue |
| `OAuth\Contract\ScopeRegistryInterface` | Read the catalogue |
| `OAuth\Contract\TokenValidatorInterface` | Validate a raw bearer token and resolve it to effective access |
| `OAuth\Dto\ResolvedAccess` | Result of validation: the Pimcore user, granted scopes, audience, client id |
| `OAuth\Contract\ResourceRegistryInterface` | Register endpoints as protected resources, making their RFC 9728 metadata resolvable |
| `OAuth\Dto\ProtectedResource` | One protected resource: canonical URI, supported scopes, authorization servers |
| `OAuth\Dto\ProtectedResourceMetadata` | The metadata document served for a resource |

Everything else under `OAuth\` is `@internal` and may change without notice. In particular, do not depend on
`OAuth\Util\CanonicalUri`: the registry canonicalises on both registration and lookup, so any equivalent form
of a URI works.

## Anatomy of an application

Five parts, in the order a request meets them.

**1. A place to authenticate.** Two shapes are in use, and the right one depends on what your bundle already
does:

- **A Symfony firewall** over your own routes, stateless, using the `pimcore_studio_backend` user provider.
  Right when your endpoints have no authentication of their own yet, or already use the security component.
  This is what the MCP servers do. Declare your own firewall over your own prefix rather than putting your
  endpoints under another bundle's URL prefix to borrow its firewall.
- **Your existing request pipeline**, if the bundle already authenticates somewhere else. Data Hub Simple
  REST checks credentials in a `ValueResolverInterface` and has no `security.yaml` at all; bolting a firewall
  on would have duplicated that and forced every installation to edit its security configuration. It added a
  branch where it already authenticated instead.

**2. Token validation.** Claim JWT-shaped bearer tokens, call `TokenValidatorInterface::validate()`, and
resolve the user. Whatever shape you chose, leave every other credential your bundle supports working: shape
is not proof, so a credential that looks like a token but does not resolve should fall through to your
existing check rather than being rejected.

**3. Resource registration.** One `ProtectedResource` per endpoint that acts as a token audience. This does
two things: it makes `/.well-known/oauth-protected-resource/<path>` resolvable, which is how a client
discovers the authorization server, and it is what the authorization endpoint validates a requested
`resource` against. One endpoint means one resource, even when it serves many logical things behind it.

Register on every request that might consult the registry. That includes your own endpoint, its metadata
document, and the OAuth endpoints, since the authorization request is validated there. Deriving the URI from
the configured issuer rather than the request host keeps registration idempotent.

**4. A 401 challenge** carrying `WWW-Authenticate: Bearer resource_metadata="…"`. Without this parameter a
standards-based client cannot begin discovery, so the whole flow never starts.

**5. Your authorization rules**, applied after authentication, wherever your bundle resolves a request to
the thing being accessed.

## Blueprint: adding an application

Data Hub Simple REST is the worked example. Where it and the MCP servers differ, both are shown.

### Step 1: Choose where to authenticate

**If your bundle has no authentication of its own**, declare a firewall.

Expose the settings as a parameter, the way other bundles do, so integrators add one line to
`security.yaml`:

```php
$container->setParameter('my_bundle.firewall_settings', [
    'pattern' => '^/my-bundle-prefix',
    'provider' => 'pimcore_studio_backend',
    'user_checker' => 'Pimcore\Security\User\UserChecker',
    'stateless' => true,
    'custom_authenticators' => [MyOAuthAuthenticator::class],
    'entry_point' => MyAuthenticationEntryPoint::class,
]);
```

Integrators then add it **before** any catch-all firewall, because Symfony picks the first matching one:

```yaml
security:
    firewalls:
        my_bundle: '%my_bundle.firewall_settings%'
```

Two decisions worth making consciously:

- **Permit anonymous requests** if your bundle already authenticates some callers itself (a static API key,
  for example). Then adding the firewall does not change behaviour for existing integrations, and OAuth is
  purely additive. The cost is that your firewall's `entry_point` never fires, so you emit the 401 challenge
  from your own error handling instead (step 4).
- **Supply an explicit rate limiter.** Symfony's default builds a per-IP tier that every client on an address
  shares, so guesses against one credential can push an unrelated valid credential into a `429`.

**If your bundle already authenticates elsewhere**, skip the firewall entirely and add a branch there. Data
Hub Simple REST does this in `McpAuthContextResolver`, so integrators need no `security.yaml` change at all
and existing traffic is untouched. The rest of the steps are the same; only step 2 changes shape.

### Step 2: Validate the token

**In a firewall**, that means an authenticator.

```php
final class MyOAuthAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly bool $oauthEnabled,
        private readonly TokenValidatorInterface $tokenValidator,
    ) {
    }

    public function supports(Request $request): bool
    {
        // Claim only JWT-shaped bearers, so other credentials fall through.
        return $this->oauthEnabled && $this->isJwtBearer($request);
    }

    public function authenticate(Request $request): Passport
    {
        $access = $this->tokenValidator->validate(
            $this->bearerToken($request),
            $this->resourceUriFor($request),
        );

        if (!$access?->user instanceof User) {
            throw new AuthenticationException('Invalid or expired access token.');
        }

        return new SelfValidatingPassport(
            new UserBadge($access->user->getUsername(), static fn () => new SecurityUser($access->user)),
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $e): ?Response
    {
        // Return null so other authenticators, or your own in-controller check, still run.
        return null;
    }
}
```

**In an existing pipeline**, it is the same three calls without the Symfony scaffolding: recognise the
credential, call `validate()`, resolve the user, and on failure continue to whatever check you had before.

Either way, gate it on `%pimcore_studio_backend.oauth.enabled%` so the code is inert when the authorization
server is switched off.

### Step 3: Register protected resources

Inject `ResourceRegistryInterface` and register one resource per endpoint that acts as an audience. The
canonical URI depends on the incoming scheme and host, so registration happens per request rather than at
compile time:

```php
$this->resourceRegistry->register(
    new ProtectedResource(
        $request->getSchemeAndHttpHost() . '/my-bundle-prefix/endpoint',
        ['mcp:read'],
        [$issuer],
    )
);
```

Gate that on the paths that actually consult the registry, your endpoint and the well-known prefix. Running
it on every request lets a varied `Host` header grow the registry unboundedly on a long-running worker.

### Step 4: Emit the challenge

An unauthenticated request must answer `401` with a `resource_metadata` pointer:

```
WWW-Authenticate: Bearer resource_metadata="https://host/.well-known/oauth-protected-resource/my-bundle-prefix/<config>"
```

`error="invalid_token"` belongs there only when a token was actually presented and rejected. Omit it when no
credential was sent at all.

### Step 5: Declare your scopes

Use your own prefix rather than another application's. Sharing `mcp:read` between two applications makes the
consent screen ambiguous about what is being granted, and prevents a token being narrowed to one of them.

```php
final class MyScopeProvider implements ScopeProviderInterface
{
    public function scopes(): array
    {
        return ['mybundle:read'];
    }
}
```

Tag the service with `ScopeProviderInterface::TAG`. The authorization endpoint then accepts the scope,
dynamic clients may register it, and the server metadata advertises it. Ship only scopes that correspond to
operations you actually have: a scope a user can consent to that grants nothing is worse than no scope.

### Step 6: Apply your own authorization

Authentication produced a Pimcore user. What that user may do is yours to decide, at the point where a
request resolves to the thing being accessed. Two rules that matter:

- **Do not apply user-bound checks to credentials that carry no user.** If your bundle also accepts a static
  key, that caller has no user, so a user allow-list must not apply to it. Getting this wrong breaks every
  existing integration the moment the firewall is added.
- **Keep admission separate from data authorization.** "May this user connect to this endpoint" and "what may
  they see once connected" are different questions with different answers, and conflating them makes both
  harder to reason about.

## Audience binding

A token is bound to the resource it was requested for. A client names it with the RFC 8707 `resource`
parameter on the authorization request; the server validates it against the registry, rejects an unknown one
with `invalid_request`, and stamps it as the token's `aud`. `TokenValidatorInterface::validate()` then refuses
a token whose audience does not name the resource URI you pass it.

This is what stops a token obtained for one application being replayed against another. Without it, every
protected resource on the installation accepts every token the server ever issued, which is a real hole once
more than one resource exists.

Two consequences for an application:

- **Pass your own resource URI to `validate()`**, and derive it the same way every time. Prefer the
  configured issuer over the request's host: the URI has to be byte-identical when the resource is
  registered, when a token is requested for it, and when that token is validated, and a Host header behind a
  proxy will not be.
- **A token with no audience is accepted.** A client that does not send `resource` gets an unbound token,
  which stays valid everywhere. That keeps existing clients working, and it means audience binding protects
  clients that opt in rather than being a wall. Do not treat the presence of an audience as guaranteed.

## What the platform does not do yet

**Scopes are not enforced.** They are requested, consented to, carried on the token and advertised per
resource, but nothing compares a granted scope against an operation. Treat a scope as a label shown at
consent time, not a guarantee, and enforce it yourself if your operations differ in privilege.

## Related

- [OAuth 2.1 Authorization Server](../02_Installation_and_Configuration/06_OAuth_Server.md) - enabling and
  configuring the authorization server
- [MCP Server Infrastructure](./08_MCP_Server.md) - the first application, and the other credentials its
  firewall accepts
- [MCP Server Management](./09_MCP_Server_Management.md) - configuring MCP servers, independent of how a
  caller authenticated
