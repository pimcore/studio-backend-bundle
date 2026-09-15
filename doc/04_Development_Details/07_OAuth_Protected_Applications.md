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
| `OAuth\Contract\ProtectedResourceProviderInterface` | Contribute your endpoints as protected resources, making their RFC 9728 metadata resolvable |
| `OAuth\Contract\ResourceRegistryInterface` | Read the protected resources this installation exposes |
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

**3. A protected-resource provider.** One `ProtectedResource` per endpoint that acts as a token audience.
This does two things: it makes `/.well-known/oauth-protected-resource/<path>` resolvable, which is how a
client discovers the authorization server, and it is what the authorization endpoint validates a requested
`resource` against. One endpoint means one resource, even when it serves many logical things behind it.

You describe them once, in a tagged service; nothing is registered per request. See
[Deriving the resource URI](#deriving-the-resource-uri) for where the URI must come from.

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

### Step 3: Declare your protected resources

Implement `ProtectedResourceProviderInterface` and describe every resource your bundle owns:

```php
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ProtectedResourceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;

final readonly class MyProtectedResourceProvider implements ProtectedResourceProviderInterface
{
    public function __construct(
        private bool $enabled,
        private ?string $issuer,
    ) {
    }

    public function resources(): iterable
    {
        // Nothing to offer while the feature is off, or before an issuer exists to
        // build a URI from. Yielding nothing fails closed: an unregistered resource is
        // refused at the authorization endpoint.
        if (!$this->enabled || $this->issuer === null) {
            return;
        }

        yield new ProtectedResource(
            $this->issuer . '/my-bundle-prefix/endpoint',
            ['mybundle:read'],
            [$this->issuer],
        );
    }
}
```

Tag the service, and pass the issuer from `pimcore_studio_backend.oauth.issuer`:

```yaml
services:
    My\Bundle\OAuth\MyProtectedResourceProvider:
        tags: ['pimcore_studio_backend.oauth.protected_resource_provider']
        arguments:
            $enabled: '%my_bundle.oauth_enabled%'
            $issuer: '%pimcore_studio_backend.oauth.issuer%'
```

**The tag is required, and `autoconfigure: true` does not apply it.** No
`registerForAutoconfiguration()` hook exists for it, so an untagged provider is never read: its metadata
document 404s, its scopes vanish from the catalogue, and a client requesting its resource is refused with
`invalid_request`. Nothing logs a warning, because from the registry's point of view the resource was never
declared. If your resource is missing, check the tag first.

The scopes are not decoration. They cap what a token for this resource may carry, a client asking for more is
narrowed to them before consent is shown, and they are how a scope comes to exist at all: the server's
catalogue is the union of what every resource supports.

Providers are read lazily and only once. Symfony's tagged iterator does not instantiate anything until the
registry is first read, and the registry memoises what it resolved, so a request touching neither OAuth nor
your endpoints pays nothing. You therefore describe every resource you own unconditionally: there is no
"current request" to filter by, which is the point. A metadata document is fetched on a `.well-known` path
that matches none of your routes, and a requested `resource` is validated on an OAuth route, so filtering
would have left both unresolvable anyway.

`ResourceRegistryInterface` is the read side of this and has no `register()`: the set of valid audiences is a
property of the configuration, and a request able to add to it is a request able to name its own audience.

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

#### Give the scope a name on the consent screen

The consent screen reads scope labels from the Pimcore Studio translation catalogue, and that is the only
place a scope is described. Ship two keys per scope in your bundle's `translations/studio.en.yaml`:

```yaml
oauth.consent.scope.mybundle-read.label: Read your products
oauth.consent.scope.mybundle-read.description: List and search the products you already have access to.
```

The slug is the scope identifier with `:` replaced by `-`, because i18next reads `:` as a namespace
separator: `mybundle:read` becomes `mybundle-read`. The `description` is optional; the `label` is what the
user reads.

A scope with no keys is still shown, as its raw identifier, so nothing is ever granted without appearing on
the screen. It reads as `mybundle:read` rather than a sentence, which is a poor thing to ask someone to
approve.

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

**Derive it from `oauth.issuer`**, on both the contributing side and the validating side. The issuer is
required whenever the server is enabled, so it is always available, and it is configured rather than supplied
by the caller. Every application in this bundle does this, the
[MCP endpoints](./08_MCP_Server.md#oauth-protected-resource) included.

**Do not derive it from the request host.** `$request->getSchemeAndHttpHost()` returns the `Host` header
unless `framework.trusted_hosts` is configured, and that is empty by default. A resource named after it lets a
caller invent an audience, obtain a token stamped with it, and then pass the audience check by replaying the
same header, so the check compares an attacker's string against the attacker's own string. Two sides deriving
the URI the same way is not sufficient; they have to agree on a value neither the caller nor a proxy can
choose. A provider is handed no request at all, which is what makes that mistake hard to make.

Because nothing reads the request host, **audience binding** needs no special handling behind a reverse
proxy. Set `oauth.issuer` to the public origin, and if you write a resource URI in configuration, write it
with the same scheme and host and no trailing slash so it matches what is contributed.

**Discovery is a different matter, and does need the proxy configured.** The RFC 9728 metadata URL in the
`401` challenge is built from the request (`McpAuthenticationEntryPoint`), and the endpoint serving that
document resolves the resource from the request too (`ProtectedResourceMetadataController`). Behind a
TLS-terminating proxy with `framework.trusted_proxies` unset, Symfony sees the internal scheme: the challenge
advertises `http://host/.well-known/oauth-protected-resource/...` while the contributed resource is
`https://...`, so the lookup misses and the document 404s. The token itself would have validated; the client
never gets far enough to present one.

Set `trusted_proxies` and `trusted_headers` so `X-Forwarded-Proto` and `X-Forwarded-Host` are honoured, and
the request-derived URL matches the configured one again.

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
