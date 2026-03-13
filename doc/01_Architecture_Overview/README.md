---
title: Architecture Overview
---

# Architecture Overview

The Studio Backend Bundle exposes a REST API built on Symfony that serves as the backend for Pimcore Studio. All endpoints are documented via OpenAPI/Swagger, providing a machine-readable API specification that can be used for client generation and interactive exploration.

## Real-Time Updates

The bundle integrates Mercure to deliver real-time updates to connected clients via Server-Sent Events (SSE). This allows Pimcore Studio to reflect changes immediately without polling.

## Core Components

### Grid System

The Grid system provides configurable columns, filters, and data transformers for listing and searching assets, data objects, and documents. It is designed to be extensible, allowing custom column types and filter logic.

- [Grid](./01_Grid.md)

### Generic Execution Engine

The Generic Execution Engine handles long-running and background tasks such as bulk operations, CSV exports, and other asynchronous jobs. It provides a unified interface for scheduling, tracking, and reporting on these tasks.

- [Generic Execution Engine](./02_Generic_Execution_Engine.md)
