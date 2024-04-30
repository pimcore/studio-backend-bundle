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