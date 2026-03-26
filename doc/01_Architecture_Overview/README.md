---
title: Architecture Overview
description: REST API design, request flow, tagged service extensibility, and core subsystems.
---

# Architecture Overview

The Studio Backend Bundle exposes a REST API built on Symfony that serves as the backend for
Pimcore Studio. All endpoints are documented via OpenAPI/Swagger attributes using the
[zircote/swagger-php](https://zircote.github.io/swagger-php/) library.

## Request Flow

A typical API request flows through these layers:

```
HTTP Request
  → Symfony Firewall (pimcore_studio authentication)
    → Route matching (#[Route] attribute)
      → Permission check (#[IsGranted] attribute, UserPermissionVoter)
        → Controller (extends AbstractApiController)
          → Service layer (business logic, data access)
            → Hydrator (transforms models to API schema objects)
              → PreResponse event (dispatched before serialization)
                → JSON serialization (Symfony Serializer)
                  → HTTP Response
```

**Controllers** extend `AbstractApiController`, which provides `jsonResponse()` for JSON
serialization and a configurable URL prefix (`{prefix}`). Controllers use Symfony's
`#[MapQueryString]` and `#[MapRequestPayload]` for type-safe parameter binding.

**Services** encapsulate business logic behind interfaces (e.g. `NoteServiceInterface`,
`LayoutServiceInterface`). Controllers depend on service interfaces, not implementations.

**Hydrators** transform Pimcore model objects into typed API schema classes. Schema classes
use OpenAPI `#[Schema]` and `#[Property]` attributes to generate the Swagger documentation.

**PreResponse events** fire after hydration but before serialization, letting subscribers add
custom attributes or modify the response. See
[Additional and Custom Attributes](../03_Extending/02_Additional_and_Custom_Attributes.md)
for the full event list.

## Authentication and Security

The bundle registers its own Symfony firewall (`pimcore_studio`) with a custom user provider
(`Pimcore\Security\User\UserProvider`). Authentication uses bearer tokens obtained through
the login endpoint. The `UserPermissionVoter` evaluates `#[IsGranted]` attributes against
permissions stored in `users_permission_definitions`.

## Tagged Service Extensibility

Most extension points follow the same pattern: implement an interface, tag the service,
and the bundle auto-discovers it at runtime. Key service tags:

| Tag | Purpose |
|-----|---------|
| `pimcore.studio_backend.update_adapter` | Element update pipeline |
| `pimcore.studio_backend.patch_adapter` | Bulk patch pipeline |
| `pimcore.studio_backend.data_adapter` | Data object field save/load |
| `pimcore.studio_backend.grid_column_definition` | Grid column type registration |
| `pimcore.studio_backend.grid_column_resolver` | Grid column value resolution |
| `pimcore.studio_backend.grid_column_collector` | Grid column availability |
| `pimcore.studio_backend.grid_transformer` | Grid data transformation |
| `pimcore.studio_backend.search_index.filter` | Search index filters (OpenSearch/ES) |
| `pimcore.studio_backend.listing.filter` | Listing filters (classic Pimcore listings) |
| `pimcore.studio_backend.field_definition_resolver` | Field definition resolution (dot notation) |
| `pimcore.studio_backend.widget_repository` | Perspective widget config storage |
| `pimcore.studio_backend.widget_hydrator` | Perspective widget config hydration |
| `pimcore.studio_backend.gdpr_data_provider` | GDPR data extraction |
| `pimcore.studio_backend.settings_provider` | Settings panel data |
| `pimcore.studio_backend.filter_service` | Filter service registration |

Most tags have dedicated guides in [Extending](../03_Extending/README.md). Tags without
a dedicated guide (`settings_provider`, `filter_service`) follow the same pattern:
implement the interface and register with the tag.

## Real-Time Updates

The bundle integrates [Mercure](https://mercure.rocks/) to deliver real-time updates to
connected Pimcore Studio clients via Server-Sent Events (SSE). When elements are created,
modified, or deleted, the backend publishes updates that connected clients receive instantly
without polling. See [Mercure Setup](../02_Installation_and_Configuration/01_Mercure_Setup.md)
for configuration details.

## Core Subsystems

### Grid System

The Grid system provides configurable columns, filters, and data transformers for listing
and searching assets, data objects, and documents. It is composed of three main components:
column definitions (what columns exist), column resolvers (how values are fetched), and
column collectors (which columns are available in a given context).

- [Grid Architecture](./01_Grid.md)

### Generic Execution Engine

The Generic Execution Engine handles long-running and background tasks such as bulk operations,
CSV/XLSX exports, ZIP uploads/downloads, and element cloning. It provides a unified interface
for scheduling, tracking progress, and reporting results.

- [Generic Execution Engine](./02_Generic_Execution_Engine.md)

### MCP Server Infrastructure (Experimental)

The bundle provides shared infrastructure for
[Model Context Protocol](https://modelcontextprotocol.io/) (MCP) servers. This includes a
dedicated `pimcore_mcp` firewall (separate from the Studio API firewall), a dual authentication
chain (session bridge for internal agent-server use, personal access tokens for external MCP
clients like Claude Desktop or Cursor), and pre-registered PSR-7/PSR-17 bridge services.
Bundles that implement MCP servers route their controllers under `/pimcore-mcp/` and get
authentication handled automatically.

- [MCP Server Infrastructure](../04_Development_Details/08_MCP_Server.md)
