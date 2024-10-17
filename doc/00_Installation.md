# Installation of the Studio Backend Bundle

:::info

 This bundle is only supported on Pimcore Core Framework 11.

:::

## Bundle Installation

To install the Studio Backend Bundle, follow the four steps below:


1) Install the required dependencies:

```bash
composer require pimcore/studio-backend-bundle
```

2) Enable Firewall settings

To enable the firewall settings in your project, add the following configuration to your `config/packages/security.yaml` file:
Keep in mind that the prefix part pimcore-studio/api can be changed to any other value in the config.
You need to adapt your access_control settings accordingly.
```yaml
security:
    firewalls: 
        pimcore_studio: '%pimcore_studio_backend.firewall_settings%'
    access_control:
      - { path: ^/pimcore-studio/api/(docs|docs.json|translations)$, roles: PUBLIC_ACCESS }
      - { path: ^/pimcore-studio, roles: ROLE_PIMCORE_USER }
```

3) Make sure the bundle is enabled in the `config/bundles.php` file. The following lines should be added:

```php
use Pimcore\Bundle\StudioBackendBundle\PimcoreStudioBackendBundle;
// ...
return [
    // ...
    PimcoreStudioBackendBundle::class => ['all' => true],
    // ...
];  
```

4) Install the bundle:

```bash
bin/console pimcore:bundle:install PimcoreStudioBackendBundle
```

## OpenApi Documentation

The Studio Backend Bundle provides an OpenApi documentation for the API. To access the documentation, navigate to the following URL:

```
https://<your-pimcore-host>/pimcore-studio/api/docs
```

You can also access the OpenApi documentation in JSON format by navigating to the following URL:
```
https://<your-pimcore-host>/pimcore-studio/api/docs.json
```

It is also possible to export the OpenApi documentation as a JSON file by running the following command:
```bash
bin/console studio-backend-bundle:generate-openapi-config-json --file-name=<your-file-name>.json
```
Filename is optional. If not provided, the default filename is `studio-backend-openapi.json`. 
The file will be saved in the `temp` directory of your Pimcore project. If the file with the same name already exists, it will be overwritten

## Setting up generic data index
Pimcore Studio Backend also requires the installation and setup of the generic data index. 
The bundle is required by default and also automatically enabled in the bundles.
To install the generic data index refer to [Generic-Data-Index](https://github.com/pimcore/generic-data-index-bundle?tab=readme-ov-file)

## Mercure

The Studio Backend Bundle uses Mercure to push updates to the frontend. To enable Mercure, you need to set up a 
Mercure hub and configure the bundle to use it.

For Mercure setup instruction, see [https://mercure.rocks/docs/hub/install](https://mercure.rocks/docs/hub/install).

The simplest way to set up Mercure is using the docker image and set up a reverse proxy on our webserver to prevent CSP issues.
For details, more aspects and some Q&A see the [Mercure Setup page](./02_Mercure_Setup.md).

### JWT Key
A valid JWT key is necessary for proper Mercure communication. Currently, the bundle uses same key for subscriber and publisher.
Use your preferred password generator to create a secure and valid JWT key, which is used by the application to encrypt JWT tokens and payloads.
The key needs to be minimum 256 bits long which is 32 characters.

> Keep the jwt_key private!
> To learn more about JWT keys and generate a valid key, e.g. take a look at https://jwt.io.

Also make sure that the keys configured in pimcore match the keys in the (docker) Mercure configuration.

### Configuration

URLs for accessing Mercure server-side (for updating state information within application
services) and client-side (for getting updates in Pimcore Studio UI) need to be configured via symfony configuration
tree as follows:

Additionally, you can configure the cookie lifetime for the JWT token in seconds. The default value is 3600 seconds.

```yaml
pimcore_studio_backend:
    mercure_settings:
        jwt_key: '<your-256-bit-secret-min-32-chars>'
        hub_url_client: 'https://your-app-domain.com/hub'
        hub_url_server: 'http://mercure/.well-known/mercure'
        # Optional configuration
        cookie_lifetime: 3600
```

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
      - { path: ^/your-prefix/api/(docs|docs.json|translations)$, roles: PUBLIC_ACCESS }
      - { path: ^/your-prefix, roles: ROLE_PIMCORE_USER }
```
