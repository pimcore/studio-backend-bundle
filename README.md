---
title: Studio Backend
---

# Pimcore Studio Backend

The Pimcore Studio Backend serves as the central hub for API endpoints and RPC calls. 
It provides a unified interface based on the OpenApi Specification for all backend calls which is accessible via swagger-ui.

![Swagger UI](./doc/img/swagger-ui.png)

It uses [zircote/swagger-php](https://github.com/zircote/swagger-php) attributes to generate the OpenApi Specification.

Swagger-ui is available at `/studio/api/docs` and the OpenApi Specification is available at `/studio/api/docs.json`.
Every description is translatable and can be found in the `studio_api_docs.en.yaml` folder of the bundle.

## Documentation Overview

- [Installation](./doc/00_Installation.md)
- [Mercure Setup](./doc/02_Mercure_Setup.md)
- [Grid](./doc/03_Grid.md)
- [Generic Execution Engine](doc/04_Generic_Execution_Engine.md)
- [Additional Attributes](./doc/05_Additional_Custom_Attributes.md)
