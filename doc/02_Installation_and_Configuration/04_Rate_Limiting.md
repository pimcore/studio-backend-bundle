# Rate Limiting

The Studio Backend Bundle includes built-in rate limiting for all API endpoints to protect against abuse and ensure fair usage.

## Overview

Rate limiting is **enabled by default**. Every request to a `/pimcore-studio/api/` or `/pimcore-mcp/` path is tracked using a sliding window algorithm, keyed by the client's IP address. When the limit is exceeded, the API responds with HTTP `429 Too Many Requests`.

Additionally, specific public endpoints have their own stricter limits that apply on top of the general one.

## Default Limits

| Limiter | Scope | Policy | Limit | Interval |
|---------|-------|--------|-------|----------|
| `studio_api_general` | All Studio API endpoints | Sliding window | 500 requests | 1 minute |
| `studio_mcp_general` | All MCP endpoints (`/pimcore-mcp/`) | Sliding window | 3000 requests | 1 minute |
| `studio_mcp_login` | Failed MCP authentication attempts | Fixed window | 5 requests | 5 minutes |
| `reset_password` | `POST /user/reset-password` | Fixed window | 5 requests | 5 minutes |
| `setting_admin_thumbnail` | `GET /setting/admin/thumbnail` | Fixed window | 60 requests | 1 minute |

The per-endpoint limits are layered on top of the general limit. For example, the `reset_password` endpoint is subject to both its own 5/5min limit and the general 500/min limit.

MCP endpoints are the exception: they use `studio_mcp_general` **instead of** `studio_api_general`, not on top of it.

`studio_mcp_login` is not a request limiter - it counts failed authentication attempts on the MCP firewall. See [MCP Server](../04_Development_Details/08_MCP_Server.md#throttling-guessed-credentials).

## Response Headers

Every Studio API and MCP response includes rate limit information in the following headers:

| Header | Description |
|--------|-------------|
| `X-RateLimit-Limit` | Maximum number of requests allowed in the current window |
| `X-RateLimit-Remaining` | Number of requests remaining before the limit is reached |
| `X-RateLimit-Reset` | Unix timestamp indicating when the current window resets |

These headers are also included on `429` responses, so clients can determine when to retry.

## Configuration

### Disabling Rate Limiting

To disable the general rate limiters entirely, add the following to your project configuration:

```yaml
# config/config.yaml
pimcore_studio_backend:
    rate_limiting:
        enabled: false
```

This switches off both `studio_api_general` and `studio_mcp_general`, and with them the `X-RateLimit-*` headers. It does **not** affect `studio_mcp_login`, which lives on the MCP firewall - see [MCP Server](../04_Development_Details/08_MCP_Server.md#throttling-guessed-credentials).

### Customizing Limits

The rate limiters use Symfony's [Rate Limiter component](https://symfony.com/doc/current/rate_limiter.html). You can override the defaults by redefining the limiter in your project's framework configuration:

```yaml
# config/packages/framework.yaml
framework:
    rate_limiter:
        studio_api_general:
            policy: 'sliding_window'
            limit: 1000
            interval: '1 minute'
```

### Storage Backend

By default, Symfony stores rate limiter state in the `cache.rate_limiter` cache pool. In a **multi-server deployment**, you must use a shared cache backend (e.g., Redis or Memcached) to ensure rate limits are enforced consistently across all servers:

```yaml
# config/packages/framework.yaml
framework:
    cache:
        pools:
            cache.rate_limiter:
                adapter: cache.adapter.redis
```

Without shared storage, each server tracks limits independently, effectively multiplying the allowed rate by the number of servers.

## Deployment Considerations

### Reverse Proxies and Load Balancers

Rate limiting is keyed by the client's IP address, obtained via Symfony's `Request::getClientIp()`. If your application runs behind a reverse proxy or load balancer, you **must** configure [trusted proxies](https://symfony.com/doc/current/deployment/proxies.html) so that the real client IP is used instead of the proxy's IP:

```yaml
# config/packages/framework.yaml
framework:
    trusted_proxies: '127.0.0.1,REMOTE_ADDR'
    trusted_headers: ['x-forwarded-for', 'x-forwarded-proto']
```

Without this configuration, all requests appear to come from the proxy's IP address, causing all users to share a single rate limit bucket.