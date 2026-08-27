---
title: MCP Server Management
description: Configure MCP servers at runtime — assign tools, control access with a read/write sharing model, and manage them in Pimcore Studio.
---

# MCP Server Management (Experimental)

An **MCP server** groups [MCP tools](../03_Extending/15_Providing_MCP_Tools.md) under a URL and controls who
may use them. Unlike the code-defined servers in
[MCP Server Infrastructure](./08_MCP_Server.md), these are **managed at runtime** — created and edited without a
deployment — through the Studio API and the Studio UI, and persisted via Pimcore's location-aware configuration.

Each server is served at `/pimcore-mcp/studio/{urlSlug}` on the `pimcore_mcp` firewall; clients authenticate with
a token from the [embedded OAuth 2.1 server](../02_Installation_and_Configuration/06_OAuth_Server.md) (or the
other authenticators in the chain).

> **Experimental.** Configuration keys, API, and UI may change between minor versions.

## The `mcp_servers` permission

A single Pimcore user permission, `mcp_servers`, gates **creating** servers and **browsing the tool catalogue**.
Grant it to users or roles in the permission editor (admins bypass all checks). It is installed with the bundle;
existing installations receive it through a migration.

Viewing and editing *existing* servers is **not** governed by this permission but per-server — see
[Access and sharing](#access-and-sharing).

## Enabling runtime editing

Servers are stored under the `studio_mcp_servers` configuration location. The default write target is
`symfony-config` (YAML), which is **read-only outside debug mode**. To create and edit servers at runtime, point
the write target at the settings store (database):

```yaml
pimcore_studio_backend:
    config_location:
        studio_mcp_servers:
            write_target:
                type: settings-store
```

When the target is read-only, the API reports `writeable: false` on every server and the UI renders read-only.
For details on storage locations, see
[Configuration Storage Locations](https://pimcore.com/docs/platform/Pimcore/Deployment/Configuration_Environments/#configuration-storage-locations--fallbacks).

## The Studio API

All endpoints live under the Studio API prefix (`/pimcore-studio/api`) and are documented in the
[OpenAPI docs](../02_Installation_and_Configuration/README.md#openapi-documentation) (tag **MCP**).

| Method · Path | Gate |
|---------------|------|
| `GET /mcp/servers` | none — returns only servers the caller can read |
| `GET /mcp/servers/{id}` | read on the server |
| `POST /mcp/servers` | `mcp_servers` |
| `PUT /mcp/servers/{id}` | write on the server |
| `DELETE /mcp/servers/{id}` | write on the server |
| `GET /mcp/tools` | `mcp_servers` |

A server's advertised `scopes` are **derived** from its tools' required scopes and cannot be set directly; the
`urlSlug` is fixed on create and locked on update.

## Access and sharing

Access is **deny-by-default** and modelled on the Pimcore Agent bundle's run/update sharing, with two levels
(**write implies read**):

- **Read** — see the server, view its configuration, copy its URL, and connect a client at runtime.
- **Write** — read, plus edit the configuration, change its sharing, and delete it.

A server carries an **owner** (its creator, implicit write), a **global read** flag (`shareGlobal`: any
authenticated user may read/use it), and per-**user** and per-**role** grants, each at read or write. For a
requested level the backend decides in order:

1. admin — always allowed
2. owner — always allowed
3. a direct user grant — authoritative (blocks role fallback, so a user can be pinned to read-only)
4. otherwise any of the user's roles that grants the level
5. read only: the global-share flag
6. otherwise denied

The **same read resolution** gates connecting to the running server at `/pimcore-mcp/studio/{urlSlug}`, so
sharing a server read with someone is exactly what lets them use it. Each server response includes
`currentUserPermissions` (`{read, write}`) — the caller's resolved access — so clients need not re-derive it.

## Managing servers in Pimcore Studio

The Studio UI presents server management as a **master/detail** screen:

- A **left rail** lists the servers the current user can read, by name, with a **New server** action (shown only
  to holders of `mcp_servers`).
- Selecting a server opens its **configuration mask**: identity (name, locked url-slug, description, enabled),
  the **tool picker** (from the catalogue, each showing its read/write scope), the **sharing panel**, and the
  derived scopes plus the copyable server URL.
- The **sharing panel** is a two-level grid: pick a user or role, then choose **Read** or **Can edit** — the same
  shape as the Agent bundle's sharing editor.
- When the current user has **read but not write** on a server (`currentUserPermissions.write === false`), the
  mask opens **read-only**: fields are disabled and Save/Delete are hidden, but copying the URL stays available —
  which is the point of a read share.

## Related

- [Providing MCP Tools](../03_Extending/15_Providing_MCP_Tools.md) — contributing the tools a server exposes.
- [MCP Server Infrastructure](./08_MCP_Server.md) — the `pimcore_mcp` firewall and authentication.
- [OAuth 2.1 Authorization Server](../02_Installation_and_Configuration/06_OAuth_Server.md) — how clients obtain
  a token.
