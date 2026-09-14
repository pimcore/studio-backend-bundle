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
| Authorization server | Authenticates the human, runs consent, issues tokens | This bundle, once enabled. Exactly one per installation. |
| Resource server | Accepts a token on its own endpoints, resolves it to a Pimcore user, decides what that user may do | Any bundle. Several per installation. |

A resource server never issues or refreshes tokens, and never needs the signing keys. It validates what it is
handed and applies its own authorization rules.

There is no revocation endpoint ([RFC 7009](https://www.rfc-editor.org/rfc/rfc7009)) and no way for a client
or an administrator to revoke a token on demand; only the library's own code and refresh-token rotation marks
records revoked. The access-token TTL is the real upper bound on a leaked token.

## Applications that accept these tokens

| Application | Endpoint | Authenticates in | Authorization model |
|---|---|---|---|
| Pimcore MCP servers | `/pimcore-mcp/…` | a Symfony firewall | The resolved user's own Pimcore permissions, plus per-server sharing |
| Datahub Simple REST | `/pimcore-datahub-webservices/simplerest…` (REST and MCP) | a request-argument resolver and a controller base class | Per-configuration allow-list of users and roles; data exposure stays driven by the Datahub configuration |

They differ deliberately, and in more than one dimension. Authentication is shared; **authorization is each
application's own business**, and so is *where* the credential is checked. The platform tells you *who* is
calling, never *what they may do*.

Datahub Simple REST shows one application with two surfaces: its REST and MCP endpoints are separate
protected resources, so a token minted for one is refused at the other, and each checks the token where it
already authenticated.

## Public contracts

| Contract | Purpose |
|----------|---------|
| `OAuth\Contract\ScopeRegistryInterface` | Read the scope catalogue |
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
  Right when your endpoints have no authentication of their own, or already use the security component.
  This is what the MCP servers do. Declare your own firewall over your own prefix rather than putting your
  endpoints under another bundle's URL prefix to borrow its firewall.
- **Your existing request pipeline**, if the bundle already authenticates somewhere else. Datahub Simple
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
the configured issuer rather than the request host keeps registration idempotent. The bundle's own MCP
authenticator is the exception rather than the model here, see [Deriving the resource URI](#deriving-the-resource-uri).

**4. A 401 challenge** carrying `WWW-Authenticate: Bearer resource_metadata="…"`. Without this parameter a
standards-based client cannot begin discovery, so the whole flow never starts.

**5. Your authorization rules**, applied after authentication, wherever your bundle resolves a request to
the thing being accessed.

## Blueprint: adding an application

Datahub Simple REST is the worked example. Where it and the MCP servers differ, both are shown.

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

**If your bundle already authenticates elsewhere**, skip the firewall entirely and add a branch there. Datahub
Simple REST does this in `McpAuthContextResolver`, so integrators need no `security.yaml` change at all
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
registry is built per request rather than at compile time, so registration happens in a `kernel.request`
subscriber:

```php
$base = $this->issuer ?? $request->getSchemeAndHttpHost();

$this->resourceRegistry->register(
    new ProtectedResource(
        $base . '/my-bundle-prefix/endpoint',
        ['mybundle:read'],
        [$base],
    )
);
```

The scopes passed here are not decoration: they cap what a token for this resource may carry, and a client
that asks for more is narrowed to them before consent is shown.

Register every resource you own on every main request, not only the one being addressed: a metadata document
is fetched on a `.well-known` request that matches none of your routes, and the authorization request is
validated on an OAuth route, so a path filter would leave those lookups unresolvable.

### Step 4: Emit the challenge

An unauthenticated request must answer `401` with a `resource_metadata` pointer:

```
WWW-Authenticate: Bearer resource_metadata="https://host/.well-known/oauth-protected-resource/my-bundle-prefix/endpoint"
```

The URL must name **the resource you registered in Step 3 and pass to `validate()` in Step 2**, not the path
of the request that produced the `401`. Those differ whenever one resource covers several endpoints: this
bundle's own MCP firewall validates every token against `https://host/pimcore-mcp`, while requests arrive at
`/pimcore-mcp/agent/<server>` and other sub-paths. Deriving the URL from the request path there would
advertise a metadata document for a resource nobody declared, and the RFC 9728 endpoint answers an
unregistered resource with `404`, so the client's discovery stops at a dead link.

`error="invalid_token"` belongs there only when a token was actually presented and rejected. Omit it when no
credential was sent at all.

### Step 5: Declare your scopes

There is nothing separate to do: **a scope exists because a resource supports it**. The `scopesSupported` you
passed to `ProtectedResource` in step 3 is the declaration.

```php
new ProtectedResource($base . '/my-bundle-prefix/endpoint', ['mybundle:read'], [$base]);
```

The authorization endpoint then accepts `mybundle:read`, dynamic clients may register it, and the server
metadata advertises it. The server-wide catalogue is the union across every registered resource.

Use your own prefix rather than another application's. Sharing `mcp:read` between two applications makes the
consent screen ambiguous about what is being granted, and prevents a token being narrowed to one of them.
Ship only scopes that correspond to operations you actually have: a scope a user can consent to that grants
nothing is worse than no scope.

Declare on each resource exactly what it accepts. A scope attached to no resource cannot be used at all: the
authorization request is narrowed to the named resource, so such a scope is either filtered out or refused
with `invalid_scope`.

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

This is what stops a token obtained for one application being replayed against another. Two consequences for
an application:

- **Pass your own resource URI to `validate()`**, and derive it the same way every time. The URI has to be
  byte-identical when the resource is declared, when a token is requested for it, and when that token is
  validated. See [Deriving the resource URI](#deriving-the-resource-uri) for the two ways to do that.
- **A token with no audience is refused.** The authorization request has to name a resource, and a token
  carrying no `aud` is rejected at every protected resource rather than accepted at all of them. Audience
  binding is a wall, not an opt-in, so a client that does not send `resource` gets an error at authorization
  time rather than a credential that fails later.

### Deriving the resource URI

Two ways, and the bundle's own applications use different ones:

- **From `oauth.issuer`.** Recommended for an application that registers its resources programmatically. The
  value is configured once, so it cannot drift with the incoming `Host` header and registration stays
  idempotent on a long-running worker.
- **From the request host** (`$request->getSchemeAndHttpHost()`). This is what
  `OAuthAccessTokenAuthenticator` does for the MCP endpoints, because the MCP resource is declared in
  `oauth.resources` rather than registered in code, and the authenticator has no configured base of its own.

The consequence for an MCP deployment behind a reverse proxy: the host Pimcore sees has to match the `uri` in
`oauth.resources` byte for byte. Set `oauth.issuer` to the public origin, configure Symfony `trusted_proxies`
so the forwarded host is honoured, and write the resource URI with the same scheme, host and no trailing
slash. Get this wrong and tokens are issued happily and then refused at the endpoint, with no error that says
why.

## What the platform leaves to each application

**Scopes are not enforced at call time.** They are requested, consented to, narrowed to the resource, carried
on the token and reported back, but nothing compares a granted scope against an operation. What a token carries
is therefore an upper bound the server maintains, not a check anyone performs: treat a scope as a label shown
at consent time, not a guarantee, and enforce it yourself if your operations differ in privilege.

## Related

- [OAuth 2.1 Authorization Server](../02_Installation_and_Configuration/06_OAuth_Server.md) - enabling and
  configuring the authorization server
- [MCP Server Infrastructure](./08_MCP_Server.md) - the first application, and the other credentials its
  firewall accepts
