---
title: Studio Backend
---

# Pimcore Studio Backend Bundle

The Studio Backend Bundle provides the REST API backend for Pimcore Studio, the administration interface for Pimcore. It handles all server-side operations that Pimcore Studio relies on, from CRUD operations on assets, data objects, and documents to real-time updates and background task processing.

## Features in a Nutshell

- OpenAPI-documented REST API for all Pimcore operations (assets, data objects, documents)
- Real-time updates via Mercure (Server-Sent Events)
- Extensible architecture: custom endpoints, filters, grid columns, document types
- Grid system with configurable columns, filters, and data transformers
- Generic Execution Engine integration for background task processing
- GDPR data extraction support
- Perspective and widget system for UI customization
- Comprehensive event system for customization hooks

## Documentation Overview

- [Architecture Overview](./01_Architecture_Overview/README.md) - REST API design, Grid system, and Execution Engine
- [Installation and Configuration](./02_Installation_and_Configuration/README.md) - Setup and configuration options
- [Extending](./03_Extending/README.md) - Adding custom endpoints, filters, columns, and more
- [Development Details](./04_Development_Details/README.md) - Technical details for working with the API
