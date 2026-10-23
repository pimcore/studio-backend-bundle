---
title: Installation and Configuration
description: Setup, security, Mercure, and configuration options for the Studio Backend Bundle.
---

# Installation of the Studio Backend Bundle

## Bundle Installation

To install the Studio Backend Bundle, follow the five steps below:

1) Make sure prerequisites are met:

- [GenericExecutionEngineBundle](https://github.com/pimcore/pimcore/blob/2026.x/doc/09_Development_Tools/01_Generic_Execution_Engine/README.md) installed and activated
- [GenericDataIndexBundle](https://github.com/pimcore/generic-data-index-bundle/blob/2026.x/doc/01_Installation/README.md) installed and activated


2) Install the required dependencies:

```bash
composer require pimcore/studio-backend-bundle
```

3) Enable Firewall settings

To enable the firewall settings in your project, add the following configuration to your
`config/packages/security.yaml` file. Keep in mind that the prefix part `pimcore-studio/api` can be changed
to any other value in the config. You need to adapt your `access_control` settings accordingly.

```yaml
security:
    firewalls:
        pimcore_studio: '%pimcore_studio_backend.firewall_settings%'
    access_control:
      - { 
            path: ^/pimcore-studio/api/(docs|docs/json|translations|user/reset-password|setting/admin/thumbnail)$, roles: PUBLIC_ACCESS 
        }
      - { path: ^/pimcore-studio/api, roles: ROLE_PIMCORE_USER }
```

**Optional: MCP firewall**

If you use bundles that provide MCP (Model Context Protocol) servers (e.g. Data Importer), add the
`pimcore_mcp` firewall as well. This enables authentication for all `/pimcore-mcp/` routes. See the
[MCP Server documentation](../04_Development_Details/08_MCP_Server.md) for details on authentication and token configuration.

```yaml
security:
    firewalls:
        pimcore_mcp: '%pimcore_studio_backend.mcp_firewall_settings%'
        pimcore_studio: '%pimcore_studio_backend.firewall_settings%'
    access_control:
      - { 
            path: ^/pimcore-studio/api/(docs|docs/json|translations|user/reset-password|setting/admin/thumbnail)$, roles: PUBLIC_ACCESS 
        }
      - { path: ^/pimcore-studio/api, roles: ROLE_PIMCORE_USER }
      - { path: ^/pimcore-mcp/, roles: ROLE_PIMCORE_USER }
```

> **Note:** The `pimcore_mcp` firewall must be listed **before** `pimcore_studio` in the firewalls section.
> Symfony evaluates firewalls in order, so placing it first ensures `/pimcore-mcp/` requests are matched
> by the correct firewall.

**Optional: OAuth 2.1 authorization server**

The bundle can also act as an embedded OAuth 2.1 authorization server, so standards-based clients can
authenticate with standards-based bearer tokens instead of static credentials. It is opt-in and its endpoints
live at the web root (outside the firewalls above), so it needs its own `access_control` rules. See the
[OAuth 2.1 Authorization Server](./06_OAuth_Server.md) page for setup and configuration.

4) Make sure the bundle is enabled in the `config/bundles.php` file. The following lines should be added:

```php
use Pimcore\Bundle\StudioBackendBundle\PimcoreStudioBackendBundle;
// ...
return [
    // ...
    PimcoreStudioBackendBundle::class => ['all' => true],
    // ...
];  
```

5) Install the bundle:

```bash
bin/console pimcore:bundle:install PimcoreStudioBackendBundle
```

## OpenApi Documentation

Access the OpenAPI documentation at:

```
https://<your-pimcore-host>/pimcore-studio/api/docs
```

JSON format:
```
https://<your-pimcore-host>/pimcore-studio/api/docs/json
```

Export the OpenAPI spec as a JSON file:
```bash
bin/console studio-backend-bundle:generate-openapi-config-json --file-name=<your-file-name>.json
```
Filename is optional. If not provided, the default filename is `studio-backend-openapi.json`. 
The file will be saved in the `temp` directory of your Pimcore project. If the file with the same name already exists, it will be overwritten

## Setting Up Generic Data Index

The Studio Backend requires the Generic Data Index bundle, which is a default dependency and
automatically enabled. For setup instructions, see the
[Generic Data Index documentation](https://github.com/pimcore/generic-data-index-bundle?tab=readme-ov-file).

## Mercure

The Studio Backend uses Mercure to push real-time updates to the frontend. Set up a Mercure hub
and configure the bundle to use it. See [Mercure official docs](https://mercure.rocks/docs/hub/install)
for general setup. The simplest approach is the Docker image with a reverse proxy to avoid CSP issues.
For detailed configuration, see the [Mercure Setup page](./01_Mercure_Setup.md).

### JWT Key
A valid JWT key is necessary for proper Mercure communication. Currently, the bundle uses the same key for subscriber and publisher.
Use your preferred password generator to create a secure and valid JWT key, which is used by the application to encrypt JWT tokens and payloads.
The key needs to be minimum 256 bits long which is 32 characters.

> Keep the jwt_key private!
> To learn more about JWT keys and generate a valid key, e.g. take a look at https://jwt.io.

Also make sure that the keys configured in pimcore match the keys in the (docker) Mercure configuration.

### Configuration

Configure a JWT key for Mercure. This key signs and verifies the JWT tokens used for authentication.

#### Optional Mercure configuration

URLs for accessing Mercure server-side (for updating state information within application
services) and client-side (for getting updates in Pimcore Studio UI) can be configured via a symfony configuration.

Additionally, you can configure the cookie parameters like:
- lifetime for the JWT token in seconds (default is 3600 seconds)
- SameSite attribute (default is `strict`, possible values are `lax`, `strict` or `none`)

```yaml
pimcore_studio_backend:
    mercure_settings:
        jwt_key: '<your-256-bit-secret-min-32-chars>'
        # Optional configuration

        # The url to the mercure hub for the (frontend) client.
        # If it is not set, the default will be set to "http(s)://<YOUR_CURRENT_PIMCORE_HOST>/hub".
        # It is possible to use "<PIMCORE_SCHEMA_HOST>" as a placeholder for the current schema and host if path to mercure should be different.
        hub_url_client: 'https://your-app-domain.com/hub'

        # The url to the mercure hub for the server. 
        # This can also be the docker container name (e.g., http://mercure/.well-known/mercure).
        # If it is not set, the default will be set to "http(s)://<YOUR_CURRENT_PIMCORE_HOST>/hub/.well-known/mercure".
        hub_url_server: 'http://mercure/.well-known/mercure'
        cookie_lifetime: 3600
        cookie_same_site: 'strict'
```

You need to configure the full URL including protocol, port and path to Mercure here.
For client side url it is also possible to use `<PIMCORE_SCHEMA_HOST>` for the current schema and host of client. 
If these settings are not set, URLs will be generated based on Pimcore host and default paths.

## Changing the prefix of the Studio Backend
It is possible to change the route where you can reach the API. By default, the route is `/pimcore-studio/api/`.  
If you want to change the prefix, you can do so by changing the configuration like the following:
Keep in mind that you need to update your access_control settings accordingly.
```yaml
pimcore_studio_backend:
  url_prefix: '/your-prefix/api/'
```

```yaml
security:
    access_control:
      - { path: ^/your-prefix/api/(docs|docs/json|translations)$, roles: PUBLIC_ACCESS }
      - { path: ^/your-prefix/api, roles: ROLE_PIMCORE_USER }
```
