---
title: Studio Backend
description: REST API backend powering Pimcore Studio.
---

# Pimcore Studio Backend Bundle

The Studio Backend Bundle provides the REST API that powers Pimcore Studio. It handles all
server-side operations: CRUD for assets, data objects, and documents, real-time updates via
Mercure, background task processing, grid data delivery, and the perspective/widget system.

For an overview of all Pimcore extension points across core, backend, and frontend layers,
see [Extending Pimcore](https://github.com/pimcore/pimcore/blob/2026.x/doc/10_Extending_Pimcore/README.md).

## Key Capabilities

- **REST API** with full OpenAPI/Swagger documentation for all Pimcore operations
- **Real-time updates** via Mercure (Server-Sent Events) for instant UI refresh
- **Grid system** with configurable columns, filters, and data transformers
- **Generic Execution Engine** for background tasks (bulk operations, exports, imports)
- **Event system** with PreResponse events for customizing API output
- **Tagged service discovery** for registering custom endpoints, filters, columns, and adapters
- **Perspective and widget system** for customizing Pimcore Studio layout
- **MCP server infrastructure** (experimental) for Model Context Protocol integrations

## Documentation

- [Architecture Overview](./01_Architecture_Overview/README.md) - API design, request flow, Grid system, and Execution Engine
- [Installation and Configuration](./02_Installation_and_Configuration/README.md) - Setup, Mercure, security, and configuration options
- [Extending](./03_Extending/README.md) - Adding custom endpoints, filters, columns, events, and adapters
- [Development Details](./04_Development_Details/README.md) - Dot notation, API testing, MCP server infrastructure
