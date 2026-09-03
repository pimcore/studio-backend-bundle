---
title: MCP Server Management
description: Configure MCP servers at runtime — assign tools, control access with per-user and per-role capabilities, and manage them in Pimcore Studio.
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
| `GET /mcp/servers` | none — returns only servers the caller can **read** |
| `GET /mcp/servers/{id}` | **Config Read** on the server |
| `POST /mcp/servers` | `mcp_servers` |
| `PUT /mcp/servers/{id}` | **Config Edit** on the server |
| `DELETE /mcp/servers/{id}` | **Config Edit** on the server |
| `GET /mcp/tools` | `mcp_servers` |

A server's advertised `scopes` are **derived** from its tools' required scopes and cannot be set directly; the
`urlSlug` is fixed on create and locked on update.

## Access and sharing

Access is **deny-by-default** with three **independent** capabilities per server:

- **Config Read** — see the server in the list and view its configuration (read-only).
- **Config Edit** — change the configuration, change its sharing, and delete it.
- **MCP Server Access** — connect a client to the *running* server over its URL.

A server carries an **owner** (its creator), a **public** flag (`shareGlobal`), and per-**user** and per-**role**
grants. Each grant is `{ name, canRead, canAccess, canEdit }` — three independent booleans, with one invariant:
**Config Edit implies Config Read** (`canRead` is normalised to true whenever `canEdit` is — never
edit-without-read). Users and roles are matched by **name**, and a user's own grant is unioned with their role
grants (most-permissive wins). The capabilities resolve independently:

- **Config Read** — admin, the owner, a public server, or a grant with `canRead`. Read is **per-grant**, so a
  user can hold **Access without Read** (use the server without ever seeing its config).
- **Config Edit** — admin, the owner, or a grant with `canEdit`.
- **MCP Server Access** — a public server, or a grant with `canAccess`. **Neither admins nor the owner get
  Access implicitly**; it must be granted explicitly (they can add themselves to the user list with Access). This
  keeps a server's *runtime* deliberately closed — managing every server does not mean being able to connect to
  one.

So the owner and admins are **symmetric**: both always have **Config Read + Config Edit**, and both need an
explicit grant for **MCP Server Access**. A **public** server (`shareGlobal: true`) grants **Config Read + MCP
Server Access** to every authenticated user (not Edit); it is then editable only by admins and the owner.

**MCP Server Access** — not read — gates connecting to the running server at `/pimcore-mcp/studio/{urlSlug}`. Each
server response includes `currentUserPermissions` (`{ canView, canAccess, canEdit }`) — the caller's resolved
capabilities — so clients need not re-derive them.

> **Back-compat.** Grants stored before `canRead` existed carry no `can_read` key; they default to **read on**,
> preserving the previous "listed = read" behaviour.

## Managing servers in Pimcore Studio

The Studio UI presents server management as a **master/detail** screen:

- A **left rail** lists the servers the current user can read, by name, with a **New server** action (shown only
  to holders of `mcp_servers`).
- Selecting a server opens its **configuration mask**: identity (name, locked url-slug, description, enabled),
  the **tool picker** (from the catalogue, each showing its read/write scope), the **sharing panel**, and the
  derived scopes plus the copyable server URL.
- The **sharing panel** is a grid with three checkbox columns per user/role — **Config Read**, **Config Edit**,
  **MCP Server Access** (a new grant defaults to `{ canRead: true, canAccess: false, canEdit: false }`). Because
  edit implies read, the UI forces **Read** on and disables its checkbox whenever **Edit** is checked. The owner
  and admins are **not** listed rows — their Config Read + Config
  Edit is implicit; to gain runtime access they add themselves as a user with **MCP Server Access**.
- When the current user has **read but not edit** (`currentUserPermissions.canEdit === false`), the mask opens
  **read-only**: fields are disabled and Save/Delete are hidden, but copying the URL stays available.

## Related

- [Providing MCP Tools](../03_Extending/15_Providing_MCP_Tools.md) — contributing the tools a server exposes.
- [MCP Server Infrastructure](./08_MCP_Server.md) — the `pimcore_mcp` firewall and authentication.
- [OAuth 2.1 Authorization Server](../02_Installation_and_Configuration/06_OAuth_Server.md) — how clients obtain
  a token.
