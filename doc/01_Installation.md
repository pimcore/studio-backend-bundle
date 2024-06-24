# Installation of the Studio Backend Bundle

:::info

 This bundle is only supported on Pimcore Core Framework 11.

:::

## Bundle Installation

To install the Studio Backend Bundle, follow the three steps below:

1) Install the required dependencies:

```bash
composer require pimcore/studio-backend-bundle
```

2) Make sure the bundle is enabled in the `config/bundles.php` file. The following lines should be added:

```php
use Pimcore\Bundle\StudioBackendBundle\PimcoreStudioBackendBundle;
// ...
return [
    // ...
    PimcoreStudioBackendBundle::class => ['all' => true],
    // ...
];  
```

3) Install the bundle:

```bash
bin/console pimcore:bundle:install PimcoreStudioBackendBundle
```

## Setting up generic data index
Pimcore Studio Backend also requires the installation and setup of the generic data index. 
The bundle is required by default and also automatically enabled in the bundles.
To install the generic data index refer to [Generic-Data-Index](https://github.com/pimcore/generic-data-index-bundle?tab=readme-ov-file)

## Enable Firewall settings

To enable the firewall settings, add the following configuration to your `config/packages/security.yaml` file:

```yaml
security:
    firewalls: 
        pimcore_studio: '%pimcore_studio_backend.firewall_settings%'
    access_control:
      - { path: ^/studio/api/(docs|docs.json|translations)$, roles: PUBLIC_ACCESS }
      - { path: ^/studio, roles: ROLE_PIMCORE_USER }
```

## Mercure

The Studio Backend Bundle uses Mercure to push updates to the frontend. To enable Mercure, you need to set up a 
Mercure hub and configure the bundle to use it.

### Setup
For Mercure setup instruction, see [https://mercure.rocks/docs/hub/install](https://mercure.rocks/docs/hub/install).

The simplest way to set up Mercure is using the docker image and set up a reverse proxy on our webserver to prevent CSP issues.
For details, more aspects and some Q&A see the [Mercure Setup page](./02_Mercure_Setup.md).

### JWT Key
A valid JWT key is necessary for proper Mercure communication. Currently, the bundle uses same key for subscriber and publisher.
Use your preferred password generator to create a secure and valid JWT key, which is used by the application to encrypt JWT tokens and payloads.

> Keep the jwt_key private!
> To learn more about JWT keys and generate a valid key, e.g. take a look at https://jwt.io.

Also make sure to configure corresponding jwt keys for Pimcore as well as for Mercure.

### Configuration

You need to define an url for the backend(server) as well as for the frontend(client) to connect to the Mercure hub.

```yaml
pimcore_studio_backend:
    mercure_settings:
        jwt_key: 'your-256-bit-secret'
        hub_url_client: 'http://localhost:8080/.well-known/mercure'
        hub_url_server: 'http://mercure/.well-known/mercure'
```
