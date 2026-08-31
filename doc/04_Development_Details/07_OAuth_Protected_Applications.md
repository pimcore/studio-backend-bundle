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

| Application | Endpoints | Authorization model |
|-------------|-----------|---------------------|
| Pimcore MCP servers | `/pimcore-mcp/…` | The resolved user's own Pimcore permissions, plus per-server sharing |
| Data Hub Simple REST | `/pimcore-datahub-webservices/simplerest/…` | Per-configuration allow-list of users and roles; data exposure stays driven by the Data Hub configuration |

They differ deliberately. Authentication is shared; **authorization is each application's own business**. The
platform tells you *who* is calling, never *what they may do*.

## Public contracts

| Contract | Purpose |
|----------|---------|
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

**1. A firewall over your own routes.** Stateless, using the `pimcore_studio_backend` user provider. Do not
put your endpoints under another bundle's URL prefix to borrow its firewall; declare your own over your own
prefix so REST-style and MCP-style endpoints in your bundle share one authentication story.

**2. An authenticator** that claims JWT-shaped bearer tokens, calls `TokenValidatorInterface::validate()`,
and puts the resolved user into the security context. It returns `null` on failure so any other credential
your bundle already supports still gets its turn.

**3. Resource registration.** One `ProtectedResource` per endpoint that acts as a token audience. This is
what makes `/.well-known/oauth-protected-resource/<path>` resolvable for your endpoint, which is how a
client discovers the authorization server in the first place.

**4. A 401 challenge** carrying `WWW-Authenticate: Bearer resource_metadata="…"`. Without this parameter a
standards-based client cannot begin discovery, so the whole flow never starts.

**5. Your authorization rules**, applied after authentication, wherever your bundle resolves a request to
the thing being accessed.

## Blueprint: adding an application

Data Hub Simple REST is the reference implementation. The steps below mirror it.

### Step 1: Declare a firewall

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

### Step 2: Write an authenticator

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

Gate it on `%pimcore_studio_backend.oauth.enabled%` so the authenticator is inert when the authorization
server is switched off.

### Step 3: Register protected resources

Inject `ResourceRegistryInterface` and register one resource per endpoint that acts as an audience. When your
endpoints are configuration-driven and therefore only known at runtime, register them as they are resolved
rather than at compile time:

```php
$this->resourceRegistry->register(
    new ProtectedResource(
        $request->getSchemeAndHttpHost() . '/my-bundle-prefix/' . $configName,
        ['mcp:read'],
        [$issuer],
    )
);
```

### Step 4: Emit the challenge

An unauthenticated request must answer `401` with a `resource_metadata` pointer:

```
WWW-Authenticate: Bearer resource_metadata="https://host/.well-known/oauth-protected-resource/my-bundle-prefix/<config>"
```

`error="invalid_token"` belongs there only when a token was actually presented and rejected. Omit it when no
credential was sent at all.

### Step 5: Apply your own authorization

Authentication produced a Pimcore user. What that user may do is yours to decide, at the point where a
request resolves to the thing being accessed. Two rules learned from the existing applications:

- **Do not apply user-bound checks to credentials that carry no user.** If your bundle also accepts a static
  key, that caller has no user, so a user allow-list must not apply to it. Getting this wrong breaks every
  existing integration the moment the firewall is added.
- **Keep admission separate from data authorization.** "May this user connect to this endpoint" and "what may
  they see once connected" are different questions with different answers, and conflating them makes both
  harder to reason about.

## What the platform does not do yet

Do not build on these until they land. Both are visible in tokens and metadata, which makes it easy to assume
they are enforced.

- **Audience binding is not enforced.** Issued tokens carry no `aud` claim, and `TokenValidatorInterface`
  accepts a resource URI but does not currently compare it. Resource URIs are discovery identifiers today,
  not an isolation boundary. Your application's own authorization is what actually restricts access.
- **Scopes are not enforced.** They are requested, consented to and carried on the token, but nothing
  compares a granted scope against an operation. Treat a scope as a label shown at consent time, not a
  guarantee.

## Related

- [OAuth 2.1 Authorization Server](../02_Installation_and_Configuration/06_OAuth_Server.md) - enabling and
  configuring the authorization server
- [MCP Server Infrastructure](./08_MCP_Server.md) - the first application, and the other credentials its
  firewall accepts
- [MCP Server Management](./09_MCP_Server_Management.md) - configuring MCP servers, independent of how a
  caller authenticated
